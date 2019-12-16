<?php

namespace morfologik\stemming;

use morfologik\exceptions\IOException;
use morfologik\fsa\FSA;

/**
 * Class Dictionary
 *
 * @package morfologik\stemming
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 6:15 PM
 */
final class Dictionary {
    /**
     * {@link FSA} automaton with the compiled dictionary data.
     * @var FSA
     */
    public $fsa;

  /**
   * Metadata associated with the dictionary.
   * @var DictionaryMetadata
   */
  public $metadata;

  /**
   * It is strongly recommended to use static methods in this class for
   * reading dictionaries.
   *
   * @param FSA $fsa
   *            An instantiated {@link FSA} instance.
   *
   * @param DictionaryMetadata $metadata
   *            A map of attributes describing the compression format and
   *            other settings not contained in the FSA automaton. For an
   *            explanation of available attributes and their possible values,
   *            see {@link DictionaryMetadata}.
   */
  public function __construct(FSA $fsa, DictionaryMetadata $metadata) {
    $this->fsa = $fsa;
    $this->metadata = $metadata;
  }

/**
 * Attempts to load a dictionary using the path to the FSA file and the
 * expected metadata extension.
 *
 * @param location The location of the dictionary file (<code>*.dict</code>).
 * @return An instantiated dictionary.
 * @throws IOException if an I/O error occurs.
 */
public static function read($location): Dictionary {
    $metadata = DictionaryMetadata::getExpectedMetadataFileName($location);

    $fsaStream = fopen($location, 'r+');
    $metadataStream = fopen($metadata, 'r+');
    if ($fsaStream === false || $metadataStream === false) {
        throw new IOException('Error, when open stream');
    }
    try {
        return self::readByStream($fsaStream, $metadataStream);
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
  }

  /**
   * Attempts to load a dictionary from opened streams of FSA dictionary data
   * and associated metadata. Input streams are not closed automatically.
   *
   * @param resource $fsaStream The stream with FSA data
   * @param resource $metadataStream The stream with metadata
   * @return Dictionary Returns an instantiated {@link Dictionary}.
   * @throws IOException if an I/O error occurs.
   */
  public static function readByStream($fsaStream, $metadataStream): Dictionary {
    return new Dictionary(FSA::read($fsaStream), DictionaryMetadata::read($metadataStream));
}
}
