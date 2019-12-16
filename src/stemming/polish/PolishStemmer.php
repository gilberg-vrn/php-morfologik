<?php

namespace morfologik\stemming\polish;

use morfologik\exceptions\IOException;
use morfologik\stemming\Dictionary;
use morfologik\stemming\DictionaryLookup;
use morfologik\stemming\IStemmer;

/**
 * Class PolishStemmer
 *
 * @package morfologik\stemming\polish
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 6:05 PM
 */
final class PolishStemmer implements IStemmer
{
    /**
     * The underlying dictionary, loaded once (lazily).
     * @var Dictionary
     */
    private static $dictionary;

    /**
     * Dictionary lookup delegate.
     * @var DictionaryLookup
     */
    private $lookup;

    public function __construct()
    {
        if (self::$dictionary == null) {
            try {
                $dictResourcePath = __DIR__ . "/pl.dict";
                if (!is_file($dictResourcePath)) {
                    throw new IOException("Polish dictionary resource not found.");
                }
                self::$dictionary = Dictionary::read($dictResourcePath);
            } catch (IOException $e) {
                throw new \RuntimeException("Could not read dictionary data.", 0, $e);
            }
        }

        $this->lookup = new DictionaryLookup(self::$dictionary);
    }

    /**
     * @return Dictionary Return the underlying {@link Dictionary} driving the stemmer.
     */
    public function getDictionary(): Dictionary
    {
        return self::$dictionary;
    }

    /**
     * {@inheritDoc}
     */
    public function lookup($word)
    {
        return $this->lookup->lookup($word);
    }
}
