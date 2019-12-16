<?php

namespace morfologik;

use morfologik\stemming\Dictionary;
use ftIndex\analyses\TokenStream;

/**
 * Class UkrainianFilter
 *
 * @package morfologik
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/2/19 9:20 PM
 */
class UkrainianTokenFilter
    extends MorfologikFilter
{
    

    public function __construct($input)
    {
        $dict = Dictionary::read(__DIR__ . '/stemming/ukrainian/ukrainian.dict');
        parent::__construct($input, $dict);
    }
}