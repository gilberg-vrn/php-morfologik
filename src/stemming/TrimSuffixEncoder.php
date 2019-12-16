<?php

namespace morfologik\stemming;

/**
 * Encodes <code>dst</code> relative to <code>src</code> by trimming whatever
 * non-equal suffix <code>src</code> has. The output code is (bytes):
 *
 * <pre>
 * {K}{suffix}
 * </pre>
 *
 * where (<code>K</code> - 'A') bytes should be trimmed from the end of
 * <code>src</code> and then the <code>suffix</code> should be appended to the
 * resulting byte sequence.
 *
 * <p>
 * Examples:
 * </p>
 *
 * <pre>
 * src: foo
 * dst: foobar
 * encoded: Abar
 *
 * src: foo
 * dst: bar
 * encoded: Dbar
 * </pre>
 */
class TrimSuffixEncoder implements ISequenceEncoder
{
    /**
     * Maximum encodable single-byte code.
     */
    const REMOVE_EVERYTHING = 255;

    public function encode($reuse, $source, $target)
    {
        $sharedPrefix = 0;
        $max = min(count($source), count($target));
        $aStart = 0;
        $bStart = 0;
        while ($sharedPrefix < $max && $source[$aStart++] == $target[$bStart++]) {
            $sharedPrefix++;
        }

//    $sharedPrefix = BufferUtils::sharedPrefixLength($source, $target);
        $truncateBytes = count($source) - $sharedPrefix;
        if ($truncateBytes >= self::REMOVE_EVERYTHING) {
            $truncateBytes = self::REMOVE_EVERYTHING;
            $sharedPrefix = 0;
        }

//$reuse = BufferUtils.ensureCapacity(reuse, 1 + target.remaining() - sharedPrefix);
        $reuse = [];

//assert target.hasArray() &&
//target.position() == 0 &&
//target.arrayOffset() == 0;

        $suffixTrimCode = (int)($truncateBytes + ord('A'));
        $reuse[] = $suffixTrimCode;
        $reuse = array_merge($reuse, array_slice($target, $sharedPrefix, count($target) - $sharedPrefix));
//    .flip();

        return $reuse;
    }

    public function decode($reuse, $source, $encoded)
    {
//    assert encoded.remaining() >= 1;

        $suffixTrimCode = $encoded[0];
        $truncateBytes = ($suffixTrimCode - ord('A')) & 0xFF;
        if ($truncateBytes == self::REMOVE_EVERYTHING) {
            $truncateBytes = count($source);
        }

        $len1 = count($source) - $truncateBytes;
        $len2 = count($encoded) - 1;

//    $reuse = BufferUtils.ensureCapacity(reuse, len1 + len2);
        $reuse = [];

//    assert source.hasArray() &&
//    source.position() == 0 &&
//    source.arrayOffset() == 0;

//    assert encoded.hasArray() &&
//    encoded.position() == 0 &&
//    encoded.arrayOffset() == 0;

        $reuse = array_slice($source, 0, $len1);
        $reuse = array_merge($reuse, array_slice($encoded, 1, $len2));
//    .flip();

        return $reuse;
    }

//  @Override
//  public String toString() {
//    return getClass().getSimpleName();
//  }
}