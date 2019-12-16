<?php

namespace morfologik\stemming\DictionaryAttribute;

use morfologik\exceptions\IllegalArgumentException;
use morfologik\stemming\DictionaryAttribute;

/**
 * Class ENCODER
 *
 * @package morfologik\stemming\DictionaryAttribute
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 7:32 PM
 */
class SEPARATOR
    extends DictionaryAttribute
{

    const PROPERTY_NAME = 'fsa.dict.separator';

    /**
     * The minimum value of a high surrogate or leading surrogate unit in UTF-16
     * encoding, {@code '\u{D800}'}.
     *
     * @since 1.5
     */
    const MIN_HIGH_SURROGATE = "\u{d800}";
    /**
     * The maximum value of a high surrogate or leading surrogate unit in UTF-16
     * encoding, {@code '\u{DBFF}'}.
     *
     * @since 1.5
     */
    const MAX_HIGH_SURROGATE = "\u{dbff}";
    /**
     * The minimum value of a low surrogate or trailing surrogate unit in UTF-16
     * encoding, {@code '\uDC00'}.
     *
     * @since 1.5
     */
    const MIN_LOW_SURROGATE = "\u{dc00}";
    /**
     * The maximum value of a low surrogate or trailing surrogate unit in UTF-16
     * encoding, {@code '\uDFFF'}.
     *
     * @since 1.5
     */
    const MAX_LOW_SURROGATE = "\u{dfff}";

    public function __construct()
    {
        parent::__construct(self::PROPERTY_NAME);
    }

    public function fromString(string $separator) {
        if ($separator == null || mb_strlen($separator) != 1) {
            throw new IllegalArgumentException("Attribute " . $this->propertyName . " must be a single character.");
        }

      if (self::isHighSurrogate($separator) ||
          self::isLowSurrogate($separator)) {
          throw new IllegalArgumentException("Field separator character cannot be part of a surrogate pair: " . $separator);
      }

      return $separator;
    }

    /**
     * Indicates whether {@code ch} is a high- (or leading-) surrogate code unit
     * that is used for representing supplementary characters in UTF-16
     * encoding.
     *
     * @param $ch
     *            the character to test.
     *
     * @return {@code true} if {@code ch} is a high-surrogate code unit;
     *         {@code false} otherwise.
     * @see    #isLowSurrogate(char)
     * @since  1.5
     */
    public static function isHighSurrogate($ch): bool
    {
        return (self::MIN_HIGH_SURROGATE <= $ch && self::MAX_HIGH_SURROGATE >= $ch);
    }

    /**
     * Indicates whether {@code ch} is a low- (or trailing-) surrogate code unit
     * that is used for representing supplementary characters in UTF-16
     * encoding.
     *
     * @param $ch
     *            the character to test.
     *
     * @return {@code true} if {@code ch} is a low-surrogate code unit;
     *         {@code false} otherwise.
     * @see    #isHighSurrogate(char)
     * @since  1.5
     */
    public static function isLowSurrogate($ch): bool
    {
        return (self::MIN_LOW_SURROGATE <= $ch && self::MAX_LOW_SURROGATE >= $ch);
    }
}