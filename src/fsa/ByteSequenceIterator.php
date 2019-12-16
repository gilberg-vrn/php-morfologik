<?php

namespace morfologik\fsa;


use morfologik\exceptions\UnsupportedOperationException;

/**
 * An iterator that traverses the right language of a given node (all sequences
 * reachable from a given node).
 */
final class ByteSequenceIterator
{
    /**
     * Default expected depth of the recursion stack (estimated longest sequence
     * in the automaton). Buffers expand by the same value if exceeded.
     */
    const EXPECTED_MAX_STATES = 15;

    /**
     * The FSA to which this iterator belongs.
     * @var FSA
     */
    private $fsa;

    /** An internal cache for the next element in the FSA */
    private $nextElement;

    /**
     * A buffer for the current sequence of bytes from the current node to the
     * root.
     */
    private $buffer = []; //new byte[EXPECTED_MAX_STATES];

    /** An arc stack for DFS when processing the automaton. */
    private $arcs = []; //new int[EXPECTED_MAX_STATES];

    /** Current processing depth in {@link #arcs}. */
    private $position = 0;

    /**
     * Create an instance of the iterator for a given node.
     *
     * @param FSA $fsa  The automaton to iterate over.
     * @param int $node The starting node's identifier (can be the {@link FSA#getRootNode()}).
     */
    public function __construct(FSA $fsa, int $node = null)
    {
        $this->fsa = $fsa;

        if ($node === null) {
            $node = $this->fsa->getRootNode();
        }

        if ($fsa->getFirstArc($node) != 0) {
            $this->restartFrom($node);
        }
    }

    /**
     * Restart walking from <code>node</code>. Allows iterator reuse.
     *
     * @param int $node Restart the iterator from <code>node</code>.
     *
     * @return self Returns <code>this</code> for call chaining.
     */
    public function restartFrom(int $node): self
    {
        $this->position = 0;
        $this->nextElement = null;

        $this->pushNode($node);
        return $this;
    }

    /** Returns <code>true</code> if there are still elements in this iterator. */
    public function hasNext()
    {
        if ($this->nextElement == null) {
            $this->nextElement = $this->advance();
        }

        return $this->nextElement != null;
    }

    /**
     * @return array Returns a {@link ByteBuffer} with the sequence corresponding to the
     *         next final state in the automaton.
     */
    public function next()
    {
        if ($this->nextElement != null) {
            $cache = $this->nextElement;
            $this->nextElement = null;
            return $cache;
        } else {
            $cache = $this->advance();
            if ($cache == null) {
                throw new NoSuchElementException();
            }
            return $cache;
        }
    }

    /**
     * Advances to the next available final state.
     */
    private final function advance()
    {
        if ($this->position == 0) {
            return null;
        }

        while ($this->position > 0) {
            $lastIndex = $this->position - 1;
            $arc = $this->arcs[$lastIndex];

            if ($arc == 0) {
                // Remove the current node from the queue.
                $this->position--;
                continue;
            }

            // Go to the next arc, but leave it on the stack
            // so that we keep the recursion depth level accurate.
            $this->arcs[$lastIndex] = $this->fsa->getNextArc($arc);

            // Expand buffer if needed.
            $bufferLength = count($this->buffer);
//      if ($lastIndex >= $bufferLength) {
//          this.buffer = Arrays.copyOf(buffer, bufferLength + EXPECTED_MAX_STATES);
//          $this->bufferWrapper = $this->buffer;
//      }
            $this->buffer[$lastIndex] = $this->fsa->getArcLabel($arc);

            if (!$this->fsa->isArcTerminal($arc)) {
                // Recursively descend into the arc's node.
                $this->pushNode($this->fsa->getEndNode($arc));
            }

            if ($this->fsa->isArcFinal($arc)) {
//          $this->bufferWrapper = [];
//          $this->bufferWrapper.limit(lastIndex + 1);
                return $this->buffer;
//          return $this->bufferWrapper;
            }
        }

        return null;
    }

    /**
     * Not implemented in this iterator.
     */
    public function remove()
    {
        throw new UnsupportedOperationException("Read-only iterator.");
    }

    /**
     * Descends to a given node, adds its arcs to the stack to be traversed.
     */
    private function pushNode(int $node)
    {
        // Expand buffers if needed.
//        if ($this->position == $this->arcs) {
//            arcs = Arrays.copyOf(arcs, arcs.length + EXPECTED_MAX_STATES);
//        }

        $this->arcs[$this->position++] = $this->fsa->getFirstArc($node);
    }
}