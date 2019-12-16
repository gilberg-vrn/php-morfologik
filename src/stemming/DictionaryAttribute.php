<?php

namespace morfologik\stemming;

use morfologik\exceptions\IllegalArgumentException;

/**
 * Class DictionaryAttribute
 *
 * @package morfologik\stemming
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 7:29 PM
 */
class DictionaryAttribute
{
    protected $propertyName;
    protected static $attrsByPropertyName;

    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public static function fromPropertyName($propertyName): DictionaryAttribute
    {
        self::initAttrs();
        if (!isset(self::$attrsByPropertyName[$propertyName])) {
            throw new IllegalArgumentException("No attribute for property: " . $propertyName);
        }

        return self::$attrsByPropertyName[$propertyName];
    }

    private static function initAttrs()
    {
        if (self::$attrsByPropertyName !== null) {
            return;
        }

        $attributes = [
            'SEPARATOR',
            'ENCODING',
            'ENCODER',
            'LICENSE',
            'AUTHOR',
            'CREATION_DATE',
            'IGNORE_DIACRITICS'
        ];
        foreach ($attributes as $attributeClassName) {
            $className = __NAMESPACE__ . '\\DictionaryAttribute\\' . $attributeClassName;
            /** @var DictionaryAttribute $class */
            $class = new $className();
            self::$attrsByPropertyName[$class->propertyName] = $class;
        }
    }

    /**
     * Converts a string to the given attribute's value.
     *
     * @param string $value The value to convert to an attribute value.
     *
     * @return mixed Returns the attribute's value converted from a string.
     *
     * @throws IllegalArgumentException
     *             If the input string cannot be converted to the attribute's
     *             value.
     */
    public function fromString(string $value)
    {
        return $value;
    }

    protected function booleanValue(string $value)
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($result === null) {
            throw new IllegalArgumentException("Not a boolean value: {$value}");
        }

        return $result;
    }

}