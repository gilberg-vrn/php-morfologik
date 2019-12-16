<?php

namespace morfologik\fsa;

/**
 * Class FSAFlags
 *
 * @package morfologik\fsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/1/19 6:45 PM
 */
class FSAFlags
{
    const FLEXIBLE = 1 << 0;
    const STOPBIT = 1 << 1;
    const NEXTBIT = 1 << 2;
    const TAILS = 1 << 3;
    const NUMBERS = 1 << 8;
    const SEPARATORS = 1 << 9;

    public static $values = [
        self::FLEXIBLE,
        self::STOPBIT,
        self::NEXTBIT,
        self::TAILS,
        self::NUMBERS,
        self::SEPARATORS,
    ];

    public static function isSet($flag, $flags)
    {
        return ($flags & $flag) != 0;
    }
}