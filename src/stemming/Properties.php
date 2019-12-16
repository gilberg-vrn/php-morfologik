<?php

namespace morfologik\stemming;

use morfologik\exceptions\IllegalArgumentException;

/**
 * Class Properties
 *
 * @package morfologik\stemming
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 6:55 PM
 */
class Properties
{
    protected $properties = [];

    public function containsKey($key)
    {
        return isset($this->properties[$key]);
    }

    public function getProperty($key, $defaultValue = null)
    {
        if (!$this->containsKey($key)) {
            return $defaultValue;
        }

        return $this->properties[$key];
    }

    public function propertyNames()
    {
        return array_keys($this->properties);
    }

    public function load($stream)
    {
        fseek($stream, 0);
        while (!feof($stream)) {
            $line = trim(fgets($stream));
            $limit = mb_strlen($line);
            if ($limit === 0) {
                continue;
            }
            if ($line[0] === '#' || $line[0] === '!') {
                continue;
            }

            $keyLen = 0;
            $valueStart = $limit;
            $hasSep = false;

            //System.out.println("line=<" + new String(lineBuf, 0, limit) + ">");
            $precedingBackslash = false;
            while ($keyLen < $limit) {
                $c = mb_substr($line, $keyLen, 1);
                //need check if escaped.
                if (($c == '=' ||  $c == ':') && !$precedingBackslash) {
                    $valueStart = $keyLen + 1;
                    $hasSep = true;
                    break;
                } else if (($c == ' ' || $c == "\t" ||  $c == "\f") && !$precedingBackslash) {
                    $valueStart = $keyLen + 1;
                    break;
                }
                if ($c == '\\') {
                    $precedingBackslash = !$precedingBackslash;
                } else {
                    $precedingBackslash = false;
                }
                $keyLen++;
            }
            while ($valueStart < $limit) {
                $c = mb_substr($line, $valueStart, 1);
                if ($c != ' ' && $c != "\t" &&  $c != "\f") {
                    if (!$hasSep && ($c == '=' ||  $c == ':')) {
                        $hasSep = true;
                    } else {
                        break;
                    }
                }
                $valueStart++;
            }
            $key = $this->loadConvert(mb_substr($line, 0, $keyLen));
            $value = $this->loadConvert(mb_substr($line, $valueStart, $limit - $valueStart));
            $this->properties[$key] = $value;
        }
    }

    private function loadConvert(string $in): string {
        $off = 0;
        $end = mb_strlen($in);
        $out = '';
        while ($off < $end) {
            $aChar = mb_substr($in, $off++, 1);
            if ($aChar == '\\') {
                $aChar = mb_substr($in, $off++, 1);
                if($aChar == 'u') {
                    // Read the xxxx
                    $value=0;
                    for ($i=0; $i<4; $i++) {
                        $aChar = mb_substr($in, $off++, 1);
                        switch ($aChar) {
                            case '0': case '1': case '2': case '3': case '4':
                            case '5': case '6': case '7': case '8': case '9':
                            $value = ($value << 4) + (ord($aChar) - ord('0'));
                            break;
                            case 'a': case 'b': case 'c':
                            case 'd': case 'e': case 'f':
                            $value = ($value << 4) + 10 + (ord($aChar) - ord('a'));
                            break;
                            case 'A': case 'B': case 'C':
                            case 'D': case 'E': case 'F':
                            $value = ($value << 4) + 10 + (ord($aChar) - ord('A'));
                            break;
                            default:
                                throw new IllegalArgumentException(
                                    "Malformed \\uxxxx encoding.");
                        }
                    }
                    $out .= \IntlChar::chr($value);
                } else {
                    if ($aChar == 't') $aChar = "\t";
                    else if ($aChar == 'r') $aChar = "\r";
                    else if ($aChar == 'n') $aChar = "\n";
                    else if ($aChar == 'f') $aChar = "\f";
                    $out .= $aChar;
                }
            } else {
                $out .= $aChar;
            }
        }
        return $out;
    }
}