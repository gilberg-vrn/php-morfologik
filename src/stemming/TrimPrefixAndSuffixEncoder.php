<?php

namespace morfologik\stemming;

/**
 * Encodes <code>dst</code> relative to <code>src</code> by trimming whatever
 * non-equal suffix and prefix <code>src</code> and <code>dst</code> have. The
 * output code is (bytes):
 *
 * <pre>
 * {P}{K}{suffix}
 * </pre>
 *
 * where (<code>P</code> - 'A') bytes should be trimmed from the start of
 * <code>src</code>, (<code>K</code> - 'A') bytes should be trimmed from the
 * end of <code>src</code> and then the <code>suffix</code> should be appended
 * to the resulting byte sequence.
 *
 * <p>
 * Examples:
 * </p>
 *
 * <pre>
 * src: abc
 * dst: abcd
 * encoded: AAd
 *
 * src: abc
 * dst: xyz
 * encoded: ADxyz
 * </pre>
 */
class TrimPrefixAndSuffixEncoder implements ISequenceEncoder {
    /**
     * Maximum encodable single-byte code.
     */
    const REMOVE_EVERYTHING = 255;

  public function encode($reuse, $source, $target) {
    // Search for the maximum matching subsequence that can be encoded.
    $maxSubsequenceLength = 0;
    $maxSubsequenceIndex = 0;
    for ($i = 0; $i < count($source); $i++) {
      // prefix at i => shared subsequence (infix)
      $sharedPrefix = BufferUtils::sharedPrefixLength($source, $i, $target, 0);
      // Only update maxSubsequenceLength if we will be able to encode it.
      if ($sharedPrefix > $maxSubsequenceLength && $i < self::REMOVE_EVERYTHING
          && (count($source) - ($i + $sharedPrefix)) < self::REMOVE_EVERYTHING) {
        $maxSubsequenceLength = $sharedPrefix;
        $maxSubsequenceIndex = $i;
      }
}

// Determine how much to remove (and where) from src to get a prefix of dst.
$truncatePrefixBytes = $maxSubsequenceIndex;
    $truncateSuffixBytes = (count($source) - ($maxSubsequenceIndex + $maxSubsequenceLength));
    if ($truncatePrefixBytes >= self::REMOVE_EVERYTHING || $truncateSuffixBytes >= self::REMOVE_EVERYTHING) {
        $maxSubsequenceIndex = $maxSubsequenceLength = 0;
        $truncatePrefixBytes = $truncateSuffixBytes = self::REMOVE_EVERYTHING;
    }

    $len1 = count($target) - $maxSubsequenceLength;
    $reuse = BufferUtils::ensureCapacity(reuse, 2 + len1);
    $reuse->clear();

//    assert target.hasArray() &&
//target.position() == 0 &&
//target.arrayOffset() == 0;

    $reuse->put((int) (($truncatePrefixBytes + 'A') & 0xFF));
    $reuse->put((int) (($truncateSuffixBytes + 'A') & 0xFF));
    $reuse->put($target->array(), maxSubsequenceLength, $len1);
    $reuse->flip();

    return $reuse;
  }

  public function decode($reuse, $source, $encoded) {
//    assert encoded.remaining() >= 2;

    $p = 0;
    $truncatePrefixBytes = ($encoded[$p]     - ord('A')) & 0xFF;
    $truncateSuffixBytes = ($encoded[$p + 1] - ord('A')) & 0xFF;

    if ($truncatePrefixBytes == self::REMOVE_EVERYTHING ||
        $truncateSuffixBytes == self::REMOVE_EVERYTHING) {
        $truncatePrefixBytes = count($source);
        $truncateSuffixBytes = 0;
    }

//    assert source.hasArray() &&
//    source.position() == 0 &&
//    source.arrayOffset() == 0;

//    assert encoded.hasArray() &&
//    encoded.position() == 0 &&
//    encoded.arrayOffset() == 0;

    $len1 = count($source) - ($truncateSuffixBytes + $truncatePrefixBytes);
    $len2 = count($encoded) - 2;
    $reuse = array_fill(0, $len1 + $len2, null);// BufferUtils::ensureCapacity($reuse, $len1 + $len2);
//    reuse.clear();

    $reuse = array_slice($source, $truncatePrefixBytes, $len1);
    $reuse = array_merge($reuse, array_slice($encoded, 2, $len2));
//    $reuse->flip();

    return $reuse;
  }

//  @Override
//  public String toString() {
//    return getClass().getSimpleName();
//  }
}