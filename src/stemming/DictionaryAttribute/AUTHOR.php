<?php

namespace morfologik\stemming\DictionaryAttribute;

use morfologik\stemming\DictionaryAttribute;

/**
 * Class ENCODER
 *
 * @package morfologik\stemming\DictionaryAttribute
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 7:32 PM
 */
class AUTHOR
    extends DictionaryAttribute
{

    public function __construct()
    {
        parent::__construct('fsa.dict.author');
    }
}