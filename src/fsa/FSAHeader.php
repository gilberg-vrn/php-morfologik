<?php

namespace morfologik\fsa;

use morfologik\exceptions\IOException;

/**
 * Class FSAHeader
 *
 * @package morfologik\fsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 8:38 PM
 */
final class FSAHeader
{
    /**
     * FSA magic (4 bytes).
     */
    const FSA_MAGIC = 0x5c667361;
//      ('\\' << 24) |
//      ('f'  << 16) |
//      ('s'  << 8)  |
//      ('a');

    /**
     * Maximum length of the header block.
     */
    const MAX_HEADER_LENGTH = 4 + 8;

    /** FSA version number. */
    public $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * Read FSA header and version from a stream, consuming read bytes.
     *
     * @param resource $in The input stream to read data from.
     *
     * @return FSAHeader Returns a valid {@link FSAHeader} with version information.
     * @throws IOException If the stream ends prematurely or if it contains invalid data.
     */
    public static function read($in): FSAHeader
    {
        $header = fread($in, 5);
        $unpackedHeader = unpack('Nmagic/cversion', $header);
        if (!isset($unpackedHeader['magic']) || $unpackedHeader['magic'] !== self::FSA_MAGIC) {
            throw new IOException("Invalid file header, probably not an FSA.");
        }
        if (!isset($unpackedHeader['version'])) {
            throw new IOException("Truncated file, no version number.");
        }

        return new FSAHeader($unpackedHeader['version']);
    }

    /**
     * Writes FSA magic bytes and version information.
     *
     * @param resource $os      The stream to write to.
     * @param int      $version Automaton version.
     *
     * @throws IOException Rethrown if writing fails.
     */
    public static function write($os, $version)
    {
        $header = pack('Nc', self::FSA_MAGIC, ord($version));
        fwrite($os, $header);
    }
}