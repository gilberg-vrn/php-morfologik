<?php

namespace morfologik\stemming;

use morfologik\exceptions\IOException;
use morfologik\stemming\DictionaryAttribute\ENCODER;

/**
 * Class DictionaryMetadata
 *
 * @package morfologik\stemming
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 6:26 PM
 */
class DictionaryMetadata
{
    /**
     * Expected metadata file extension.
     */
    const METADATA_FILE_EXTENSION = "info";

    /** @var DictionaryAttribute[] */
    protected $attributes;

    /** @var EncoderType */
    protected $encoderType;

    protected $separator;
    protected $separatorChar;

    /**
     * DictionaryMetadata constructor.
     *
     * @param DictionaryAttribute[][] $attrs
     */
    public function __construct($attrs)
    {
        $this->attributes = $attrs;
        $this->encoderType = $this->attributes[DictionaryAttribute\ENCODER::PROPERTY_NAME]['attr']->fromString($this->attributes[DictionaryAttribute\ENCODER::PROPERTY_NAME]['value']);
        $this->separatorChar = $this->attributes[DictionaryAttribute\SEPARATOR::PROPERTY_NAME]['attr']->fromString($this->attributes[DictionaryAttribute\SEPARATOR::PROPERTY_NAME]['value']);
        $this->separator = \IntlChar::ord($this->separatorChar);
    }

    public static function getExpectedMetadataFileName(string $dictionaryFile): string
    {
        $dotIndex = mb_strrpos($dictionaryFile, '.');
        if ($dotIndex === false) {
            $featuresName = $dictionaryFile . "." . self::METADATA_FILE_EXTENSION;
        } else {
            $featuresName = mb_substr($dictionaryFile, 0, $dotIndex) . "." . self::METADATA_FILE_EXTENSION;
        }

        return $featuresName;
    }

    public static function read($metadataStream)
    {
        $properties = new Properties();
        $properties->load($metadataStream);

        if (!$properties->containsKey(ENCODER::PROPERTY_NAME)) {
            throw new IOException("Deprecation error when read metadata");
        }

        $map = [];
        foreach ($properties->propertyNames() as $propertyName) {
            $map[$propertyName] = [
                'value' => $properties->getProperty($propertyName),
                'attr' => DictionaryAttribute::fromPropertyName($propertyName),
            ];
        }

        return new DictionaryMetadata($map);
    }

    /**
     * @return EncoderType
     */
    public function getSequenceEncoderType()
    {
        return $this->encoderType;
    }

    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @return DictionaryAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getSeparatorAsChar()
    {
        return $this->separatorChar;
    }

    public function getInputConversionPairs()
    {
        return [];
    }

    public function getOutputConversionPairs()
    {
        return [];
    }
}