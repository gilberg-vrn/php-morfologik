<?php

namespace morfologik\stemming;

/**
 * Class EncoderType
 *
 * @package morfologik\stemming
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 7:39 PM
 */
abstract class EncoderType
{
    public static function valueOf($value)
    {
        switch ($value) {
            case 'SUFFIX':
                return new class() extends EncoderType
                {
                    public function get()
                    {
                        return new TrimSuffixEncoder();
                    }
                };
            case 'PREFIX':
                return new class() extends EncoderType
                {
                    public function get()
                    {
                        return new TrimPrefixAndSuffixEncoder();
                    }
                };
            case 'INFIX':
                return new class() extends EncoderType
                {
                    public function get()
                    {
                        return new TrimInfixAndSuffixEncoder();
                    }
                };
            case 'NONE':
                return new class() extends EncoderType
                {
                    public function get()
                    {
                        return new NoEncoder();
                    }
                };
        }
    }

    abstract public function get();
}