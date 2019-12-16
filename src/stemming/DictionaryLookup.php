<?php

namespace morfologik\stemming;

use morfologik\exceptions\IllegalArgumentException;
use morfologik\fsa\ByteSequenceIterator;
use morfologik\fsa\FSA;
use morfologik\fsa\FSATraversal;
use morfologik\fsa\MatchResult;

/**
 * This class implements a dictionary lookup over an FSA dictionary. The
 * dictionary for this class should be prepared from a text file using Jan
 * Daciuk's FSA package (see link below).
 *
 * <p>
 * <b>Important:</b> finite state automatons in Jan Daciuk's implementation use
 * <em>bytes</em> not unicode characters. Therefore objects of this class always
 * have to be constructed with an encoding used to convert Java strings to byte
 * arrays and the other way around. You <b>can</b> use UTF-8 encoding, as it
 * should not conflict with any control sequences and separator characters.
 *
 * @see <a href="http://www.eti.pg.gda.pl/~jandac/fsa.html">FSA package Web
 *      site</a>
 */
final class DictionaryLookup implements IStemmer
{
    /**
     * An FSA used for lookups.
     * @var FSATraversal
     */
    private $matcher;

    /**
     * An iterator for walking along the final states of {@link #fsa}.
     * @var ByteSequenceIterator
     */
    private $finalStatesIterator;

    /** FSA's root node. */
    private $rootNode;

    /** Expand buffers and arrays by this constant. */
    const EXPAND_SIZE = 10;

    /**
     * Private internal array of reusable word data objects.
     * @var WordData[]
     */
    private $forms = []; //new WordData[0];

    /** A "view" over an array implementing */
    private $formsList = []; //new ArrayViewList<WordData>(
//    forms, 0, forms.length);

    /**
     * Features of the compiled dictionary.
     *
     * @var DictionaryMetadata
     * @see DictionaryMetadata
     */
    private $dictionaryMetadata;

    /**
     * Charset encoder for the FSA.
     * @var CharsetEncoder
     */
    private $encoder;

    /**
     * Charset decoder for the FSA.
     * @var CharsetDecoder
     */
    private $decoder;

    /**
     * The FSA we are using.
     * @var FSA
     */
    private $fsa;

    /**
     * @see #getSeparatorChar()
     */
    private $separatorChar;

    /**
     * Internal reusable buffer for encoding words into byte arrays using
     * {@link #encoder}.
     */
    private $byteBuffer = []; //ByteBuffer.allocate(0);

    /**
     * Internal reusable buffer for encoding words into byte arrays using
     * {@link #encoder}.
     */
    private $charBuffer = []; //CharBuffer.allocate(0);

    /**
     * Reusable match result.
     * @var MatchResult
     */
    private $matchResult = null;

    /**
     * The {@link Dictionary} this lookup is using.
     * @var Dictionary
     */
    private $dictionary;

    /** @var ISequenceEncoder */
    private $sequenceEncoder;

    /**
     * Creates a new object of this class using the given FSA for word lookups
     * and encoding for converting characters to bytes.
     *
     * @param dictionary The dictionary to use for lookups.
     *
     * @throws IllegalArgumentException
     *             if FSA's root node cannot be acquired (dictionary is empty).
     */
    public function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
        $this->dictionaryMetadata = $dictionary->metadata;
        $this->sequenceEncoder = $dictionary->metadata->getSequenceEncoderType()->get();
        $this->rootNode = $dictionary->fsa->getRootNode();
        $this->fsa = $dictionary->fsa;
        $this->matcher = new FSATraversal($this->fsa);
        $this->matchResult = new MatchResult();
        $this->finalStatesIterator = new ByteSequenceIterator($this->fsa, $this->fsa->getRootNode());

        if ($this->dictionaryMetadata == null) {
            throw new IllegalArgumentException("Dictionary metadata must not be null.");
        }

//        $this->decoder = $dictionary->metadata->getDecoder();
//        $this->encoder = $dictionary->metadata->getEncoder();
        $this->separatorChar = $dictionary->metadata->getSeparatorAsChar();
    }

    /**
     * Searches the automaton for a symbol sequence equal to <code>word</code>,
     * followed by a separator. The result is a stem (decompressed accordingly
     * to the dictionary's specification) and an optional tag data.
     */
    public function lookup($word): array
    {
        $separator = $this->dictionaryMetadata->getSeparator();

        if (!empty($this->dictionaryMetadata->getInputConversionPairs())) {
            $word = $this->applyReplacements($word, $this->dictionaryMetadata->getInputConversionPairs());
        }

        // Reset the output list to zero length.
        $this->formsList = [];//->wrap($this->forms, 0, 0);

        // Encode word characters into bytes in the same encoding as the FSA's.
        $this->charBuffer = [];
//    $this->charBuffer = BufferUtils.ensureCapacity(charBuffer, word.length());
        for ($i = 0; $i < mb_strlen($word); $i++) {
            $chr = mb_substr($word, $i, 1);
            if ($chr == $this->separatorChar) {
                return $this->formsList;
            }
            $this->charBuffer[] = $chr;
        }
//    $this->charBuffer->flip();
        $this->byteBuffer = $this->charsToBytes($this->charBuffer, $this->byteBuffer);

        // Try to find a partial match in the dictionary.
        $match = $this->matcher->match($this->matchResult, $this->byteBuffer, 0, count($this->byteBuffer), $this->rootNode);

        if ($match->kind == MatchResult::SEQUENCE_IS_A_PREFIX) {
            /*
             * The entire sequence exists in the dictionary. A separator should
             * be the next symbol.
             */
            $arc = $this->fsa->getArc($match->node, $separator);

            /*
             * The situation when the arc points to a final node should NEVER
             * happen. After all, we want the word to have SOME base form.
             */
            if ($arc != 0 && !$this->fsa->isArcFinal($arc)) {
                // There is such a word in the dictionary. Return its base forms.
                $formsCount = 0;

                $this->finalStatesIterator->restartFrom($this->fsa->getEndNode($arc));
                while ($this->finalStatesIterator->hasNext()) {
                    $bb = $this->finalStatesIterator->next();
                    $ba = $bb;
                    $bbSize = count($bb);

                    if ($formsCount >= count($this->forms)) {
//              $this->forms = Arrays.copyOf(forms, forms.length + EXPAND_SIZE);
                        $limit = count($this->forms) + self::EXPAND_SIZE;
                        for ($k = 0; $k < $limit; $k++) {
                            if (!isset($this->forms[$k]) || $this->forms[$k] == null) {
                                $this->forms[$k] = new WordData($this->decoder);
                            }
                        }
                    }

                    /*
                     * Now, expand the prefix/ suffix 'compression' and store
                     * the base form.
                     */
                    $wordData = $this->forms[$formsCount++];
                    if (empty($this->dictionaryMetadata->getOutputConversionPairs())) {
                        $wordData->update($this->byteBuffer, $word);
                    } else {
                        $wordData->update($this->byteBuffer, $this->applyReplacements($word, $this->dictionaryMetadata->getOutputConversionPairs()));
                    }

                    /*
                     * Find the separator byte's position splitting the inflection instructions
                     * from the tag.
                     */
                    for ($sepPos = 0; $sepPos < $bbSize; $sepPos++) {
                        if ($ba[$sepPos] == $separator) {
                            break;
                        }
                    }

                    /*
                     * Decode the stem into stem buffer.
                     */
                    $wordData->stemBuffer = $this->sequenceEncoder->decode($wordData->stemBuffer,
                        $this->byteBuffer,
                        array_slice($ba, 0, $sepPos));

                    // Skip separator character.
                    $sepPos++;

                    /*
                     * Decode the tag data.
                     */
                    $tagSize = $bbSize - $sepPos;
                    if ($tagSize > 0) {
//              $wordData->tagBuffer = BufferUtils.ensureCapacity($wordData->tagBuffer, tagSize);
                        $wordData->tagBuffer = array_slice($ba, $sepPos, $tagSize);
//                        $wordData->tagBuffer->flip();
                    }
                }

                $this->formsList = array_slice($this->forms, 0, $formsCount);//->wrap($this->forms, 0, $formsCount);
            }
        } else {
            /*
             * this case is somewhat confusing: we should have hit the separator
             * first... I don't really know how to deal with it at the time
             * being.
             */
        }
        return $this->formsList;
    }

    private static function substr_replace($input, $replacement, $offset, $length = null)
    {
        if ($offset > 0) {
            $prefix = mb_substr($input, 0, $offset - 1);
        } else {
            $prefix = '';
        }

        if ($length !== null) {
            $length = min(mb_strlen($replacement), $length);
        } else {
            $length = mb_strlen($replacement);
        }

        if ($offset + $length < mb_strlen($input)) {
            $postfix = mb_substr($input, $offset + $length);
        } else {
            $postfix = '';
        }

        return $prefix . $replacement . $postfix;
    }

    /**
     * Apply partial string replacements from a given map.
     *
     * Useful if the word needs to be normalized somehow (i.e., ligatures,
     * apostrophes and such).
     *
     * @param string $word         The word to apply replacements to.
     * @param array  $replacements A map of replacements (from-&gt;to).
     *
     * @return string Returns a new string with all replacements applied.
     */
    public static function applyReplacements($word, array $replacements): string
    {
        // quite horrible from performance point of view; this should really be a transducer.
        $sb = $word;
        foreach ($replacements as $key => $e) {
            $index = mb_strpos($sb, $key);
            while ($index !== false) {
                $sb = self::substr_replace($sb, $e, $index);
                $index = mb_strpos($sb, $key, $index + mb_strlen($key));
            }
        }
        return $sb;
    }

    /**
     * Encode a character sequence into a byte buffer, optionally expanding
     * buffer.
     */
    private function charsToBytes($chars, $bytes): array
    {
        $bytes = [];
        foreach ($chars as $char) {
            $byte = \IntlChar::ord($char);
            if ($byte > 255) {
                foreach (unpack('c*', $char) as $byte) {
                    $bytes[] = $byte;
                }
            } else {
                $bytes[] = $byte;
            }
        }

        return $bytes;
    }

    /**
     * Return an iterator over all {@link WordData} entries available in the
     * embedded {@link Dictionary}.
     */
//  public Iterator<WordData> iterator() {
//    return new DictionaryIterator(dictionary, decoder, true);
//  }

    /**
     * @return Dictionary Return the {@link Dictionary} used by this object.
     */
    public function getDictionary(): Dictionary
    {
        return $this->dictionary;
    }

    /**
     * @return string Returns the logical separator character splitting inflected form,
     *         lemma correction token and a tag. Note that this character is a best-effort
     *         conversion from a byte in {@link DictionaryMetadata#separator} and
     *         may not be valid in the target encoding (although this is highly unlikely).
     */
    public function getSeparatorChar(): string
    {
        return $this->separatorChar;
    }
}