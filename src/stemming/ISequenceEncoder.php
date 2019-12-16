<?php

namespace morfologik\stemming;

/**
 * The logic of encoding one sequence of bytes relative to another sequence of
 * bytes. The "base" form and the "derived" form are typically the stem of
 * a word and the inflected form of a word.
 *
 * <p>Derived form encoding helps in making the data for the automaton smaller
 * and more repetitive (which results in higher compression rates).
 *
 * <p>See example implementation for details.
 */
interface ISequenceEncoder {
    /**
     * Encodes <code>target</code> relative to <code>source</code>,
     * optionally reusing the provided {@link ByteBuffer}.
     *
     * @param array $reuse Reuses the provided {@link ByteBuffer} or allocates a new one if there is not enough remaining space.
     * @param array $source The source byte sequence.
     * @param array $target The target byte sequence to encode relative to <code>source</code>
     * @return array Returns the {@link ByteBuffer} with encoded <code>target</code>.
     */
    public function encode($reuse, $source, $target);

  /**
   * Decodes <code>encoded</code> relative to <code>source</code>,
   * optionally reusing the provided {@link ByteBuffer}.
   *
   * @param array $reuse Reuses the provided {@link ByteBuffer} or allocates a new one if there is not enough remaining space.
   * @param array $source The source byte sequence.
   * @param array $encoded The {@linkplain #encode previously encoded} byte sequence.
   * @return array Returns the {@link ByteBuffer} with decoded <code>target</code>.
   */
  public function decode($reuse, $source, $encoded);
}