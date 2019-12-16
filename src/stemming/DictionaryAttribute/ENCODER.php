<?php

namespace morfologik\stemming\DictionaryAttribute;

use morfologik\stemming\DictionaryAttribute;
use morfologik\stemming\EncoderType;

/**
 * Class ENCODER
 *
 * @package morfologik\stemming\DictionaryAttribute
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 7:32 PM
 */
class ENCODER
    extends DictionaryAttribute
{
    const PROPERTY_NAME = 'fsa.dict.encoder';

    public function __construct()
    {
        parent::__construct(self::PROPERTY_NAME);
    }

    public function fromString(string $value)
    {
        return EncoderType::valueOf(mb_strtoupper($value));
    }
}