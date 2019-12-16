<?php

namespace morfologik\stemming;

/**
 * Stem and tag data associated with a given word.
 *
 * Instances of this class are reused and mutable (values
 * returned from {@link #getStem()}, {@link #getWord()}
 * and other related methods change on subsequent calls to
 * {@link DictionaryLookup} class that returned a given
 * instance of {@link WordData}.
 *
 * If you need a copy of the
 * stem or tag data for a given word, you have to create a custom buffer
 * yourself and copy the associated data, perform {@link #clone()} or create
 * strings (they are immutable) using {@link #getStem()} and then
 * {@link CharSequence#toString()}.
 *
 * For reasons above it makes no sense to use instances
 * of this class in associative containers or lists. In fact,
 * both {@link #equals(Object)} and {@link #hashCode()} are overridden and throw
 * exceptions to prevent accidental damage.
 */
final class WordData
{
    /**
     * Error information if somebody puts us in a Java collection.
     */
    const COLLECTIONS_ERROR_MESSAGE = "Not suitable for use in Java collections framework (volatile content). Refer to documentation.";

    /** Character encoding in internal buffers. */
    private $decoder;

    /**
     * Inflected word form data.
     */
    private $wordCharSequence;

    /**
     * Character sequence after converting {@link #stemBuffer} using
     * {@link #decoder}.
     */
    private $stemCharSequence;

    /**
     * Character sequence after converting {@link #tagBuffer} using
     * {@link #decoder}.
     */
    private $tagCharSequence;

    /** Byte buffer holding the inflected word form data. */
    public $wordBuffer;

    /** Byte buffer holding stem data. */
    public $stemBuffer;

    /** Byte buffer holding tag data. */
    public $tagBuffer;

    /**
     * Package scope constructor.
     */
    public function __construct($decoder)
    {
        $this->decoder = $decoder;

        $this->stemBuffer = [];
        $this->tagBuffer = [];
        $this->stemCharSequence = [];
        $this->tagCharSequence = [];
    }

    /**
     * A constructor for tests only.
     */
//WordData(String stem, String tag, String encoding) {
//    this(Charset.forName(encoding).newDecoder());
//
//    try {
//        if (stem != null)
//            stemBuffer.put(stem.getBytes(encoding));
//        if (tag != null)
//            tagBuffer.put(tag.getBytes(encoding));
//    } catch (UnsupportedEncodingException e) {
//        throw new RuntimeException(e);
//    }
//    }

    /**
     * Copy the stem's binary data (no charset decoding) to a custom byte
     * buffer. If the buffer is null or not large enough to hold the result, a
     * new buffer is allocated.
     *
     * @param target
     *            Target byte buffer to copy the stem buffer to or
     *            <code>null</code> if a new buffer should be allocated.
     *
     * @return Returns <code>target</code> or the new reallocated buffer.
     */
//    public ByteBuffer getStemBytes(ByteBuffer target) {
//    target = BufferUtils.ensureCapacity(target, stemBuffer.remaining());
//    assert target.position() == 0;
//        stemBuffer.mark();
//        target.put(stemBuffer);
//        stemBuffer.reset();
//        target.flip();
//        return target;
//    }

    /**
     * Copy the tag's binary data (no charset decoding) to a custom byte buffer.
     * If the buffer is null or not large enough to hold the result, a new
     * buffer is allocated.
     *
     * @param target
     *            Target byte buffer to copy the tag buffer to or
     *            <code>null</code> if a new buffer should be allocated.
     *
     * @return Returns <code>target</code> or the new reallocated buffer.
     */
//    public ByteBuffer getTagBytes(ByteBuffer target) {
//    target = BufferUtils.ensureCapacity(target, tagBuffer.remaining());
//    assert target.position() == 0;
//        tagBuffer.mark();
//        target.put(tagBuffer);
//        tagBuffer.reset();
//        target.flip();
//        return target;
//    }

    /**
     * Copy the inflected word's binary data (no charset decoding) to a custom
     * byte buffer. If the buffer is null or not large enough to hold the
     * result, a new buffer is allocated.
     *
     * @param target
     *            Target byte buffer to copy the word buffer to or
     *            <code>null</code> if a new buffer should be allocated.
     *
     * @return Returns <code>target</code> or the new reallocated buffer.
     */
//    public ByteBuffer getWordBytes(ByteBuffer target) {
//    target = BufferUtils.ensureCapacity(target, wordBuffer.remaining());
//    assert target.position() == 0;
//        wordBuffer.mark();
//        target.put(wordBuffer);
//        wordBuffer.reset();
//        target.flip();
//        return target;
//    }

    /**
     * @return array Return tag data decoded to a character sequence or
     *         <code>null</code> if no associated tag data exists.
     */
    public function getTag()
    {
        $this->tagCharSequence = $this->decode($this->tagBuffer, $this->tagCharSequence);
        return count($this->tagCharSequence) == 0 ? null : $this->tagCharSequence;
    }

    /**
     * @return array Return stem data decoded to a character sequence or
     *         <code>null</code> if no associated stem data exists.
     */
    public function getStem()
    {
        $this->stemCharSequence = $this->decode($this->stemBuffer, $this->stemCharSequence);
        return count($this->stemCharSequence) == 0 ? null : $this->stemCharSequence;
    }

    /**
     * @return Return inflected word form data. Usually the parameter passed to
     *         {@link DictionaryLookup#lookup(CharSequence)}.
     */
    public function getWord()
    {
        return $this->wordCharSequence;
    }


//    public String toString() {
//        return "WordData["
//            + this.getWord() + ","
//            + this.getStem() + ","
//            + this.getTag() + "]";
//    }

    /**
     * Decode byte buffer, optionally expanding the char buffer to.
     */
    private function decode($bytes, $chars)
    {
        $chars = [];
        if ($this->decoder !== null) {
            $maxCapacity = (int)(count($bytes) * $this->decoder->maxCharsPerByte());
//        if (chars.capacity() <= maxCapacity) {
//            chars = CharBuffer.allocate(maxCapacity);
//        }

//        bytes.mark();
            $this->decoder->reset();
            $this->decoder->decode($bytes, $chars, true);
//        chars.flip();
//        bytes.reset();

        } else {
            $bytesLen = count($bytes);
            for ($i = 0; $i < $bytesLen; $i++) {
                $char = pack('c', $bytes[$i]);
                $ord = ord($char);
                if ($ord < 128) {
                    $chars[] = $char;
                    continue;
                } elseif ($ord < 2048) {
                    $skip = 1;
                } elseif ($ord < 65536) {
                    $skip = 2;
                } else {
                    $skip = 3;
                }
                for ($j = 1; $j <= $skip; $j++) {
                    $char .= pack('c', $bytes[$i + $j]);
                }
                $i+=$skip;
                $chars[] = $char;
            }
        }

        return $chars;
    }

    public function update($wordBuffer, $word)
    {
        $this->stemCharSequence = [];
        $this->tagCharSequence = [];
        $this->stemBuffer = [];
        $this->tagBuffer = [];

        $this->wordBuffer = $wordBuffer;
        $this->wordCharSequence = $word;
    }
}
