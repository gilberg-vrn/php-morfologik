<?php

namespace morfologik;

use morfologik\stemming\Dictionary;
use morfologik\stemming\DictionaryLookup;
use morfologik\stemming\IStemmer;
use morfologik\stemming\polish\PolishStemmer;
use morfologik\stemming\WordData;
use ftIndex\analyses\TokenStream;

/**
 * Class MorfologikFilter
 *
 * @package morfologik
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 5:51 PM
 */
class MorfologikFilter extends TokenStream
{

    private $current;
    /** @var IStemmer */
    private $stemmer;

    /** @var WordData[] */
    private $lemmaList = [];

    private $lemmaListIndex;

    /**
     * Creates a filter with the default (Polish) dictionary.
     */
    public function __construct($in, Dictionary $dict = null)
    {
        if ($dict === null) {
            $dict = (new PolishStemmer())->getDictionary();
        }
        parent::__construct($in);
        $this->stemmer = new DictionaryLookup($dict);
        $this->lemmaList = [];
    }

    /**
     * A pattern used to split lemma forms.
     */
    private $lemmaSplitter = "/\\+|\\|/";

    private function popNextLemma()
    {
        // One tag (concatenated) per lemma.
        $lemma = $this->lemmaList[$this->lemmaListIndex++];
        $this->termAttribute = implode('', $lemma->getStem());
        $tag = $lemma->getTag();
        if ($tag != null) {
            $this->tagsAttribute = preg_split($this->lemmaSplitter, implode('', $tag));
        } else {
            $this->tagsAttribute = [];
        }
    }

    /**
     * Lookup a given surface form of a token and update
     * {@link #lemmaList} and {@link #lemmaListIndex} accordingly.
     */
    private function lookupSurfaceForm($token): bool
    {
        $this->lemmaList = $this->stemmer->lookup($token);
        $this->lemmaListIndex = 0;
        return count($this->lemmaList) > 0;
    }

    /** Retrieves the next token (possibly from the list of lemmas). */
    public final function incrementToken(): bool
    {
        if ($this->lemmaListIndex < count($this->lemmaList)) {
            $this->restoreState($this->current);
            $this->posIncrAtt = 1;
            $this->popNextLemma();
            return true;
        } else {
            if ($this->input->incrementToken()) {
                if (!$this->keywordAttribute &&
                    ($this->lookupSurfaceForm($this->termAttribute) || $this->lookupSurfaceForm(mb_strtolower($this->termAttribute)))) {
                    $this->current = $this->captureState();
                    $this->popNextLemma();
                } else {
                    $this->tagsAttribute = [];
                }
                return true;
            } else {
                return false;
            }
        }
    }
}
