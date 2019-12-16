<?php

namespace morfologik\fsa;


use morfologik\exceptions\IOException;

/**
 * CFSA (Compact Finite State Automaton) binary format implementation, version 2:
 * <ul>
 *  <li>{@link #BIT_TARGET_NEXT} applicable on all arcs, not necessarily the last one.</li>
 *  <li>v-coded goto field</li>
 *  <li>v-coded perfect hashing numbers, if any</li>
 *  <li>31 most frequent labels integrated with flags byte</li>
 * </ul>
 *
 * <p>The encoding of automaton body is as follows.</p>
 *
 * <pre>
 * ---- CFSA header
 * Byte                            Description
 *       +-+-+-+-+-+-+-+-+\
 *     0 | | | | | | | | | +------ '\'
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     1 | | | | | | | | | +------ 'f'
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     2 | | | | | | | | | +------ 's'
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     3 | | | | | | | | | +------ 'a'
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     4 | | | | | | | | | +------ version (fixed 0xc6)
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     5 | | | | | | | | | +----\
 *       +-+-+-+-+-+-+-+-+/      \ flags [MSB first]
 *       +-+-+-+-+-+-+-+-+\      /
 *     6 | | | | | | | | | +----/
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     7 | | | | | | | | | +------ label lookup table size
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *  8-32 | | | | | | | | | +------ label value lookup table
 *       : : : : : : : : : |
 *       +-+-+-+-+-+-+-+-+/
 *
 * ---- Start of a node; only if automaton was compiled with NUMBERS option.
 *
 * Byte
 *        +-+-+-+-+-+-+-+-+\
 *      0 | | | | | | | | | \
 *        +-+-+-+-+-+-+-+-+  +
 *      1 | | | | | | | | |  |      number of strings recognized
 *        +-+-+-+-+-+-+-+-+  +----- by the automaton starting
 *        : : : : : : : : :  |      from this node. v-coding
 *        +-+-+-+-+-+-+-+-+  +
 *        | | | | | | | | | /
 *        +-+-+-+-+-+-+-+-+/
 *
 * ---- A vector of this node's arcs. An arc's layout depends on the combination of flags.
 *
 * 1) NEXT bit set, mapped arc label.
 *
 *        +----------------------- node pointed to is next
 *        | +--------------------- the last arc of the node
 *        | | +------------------- this arc leads to a final state (acceptor)
 *        | | |  _______+--------- arc's label; indexed if M &gt; 0, otherwise explicit label follows
 *        | | | / | | | |
 *       +-+-+-+-+-+-+-+-+\
 *     0 |N|L|F|M|M|M|M|M| +------ flags + (M) index of the mapped label.
 *       +-+-+-+-+-+-+-+-+/
 *       +-+-+-+-+-+-+-+-+\
 *     1 | | | | | | | | | +------ optional label if M == 0
 *       +-+-+-+-+-+-+-+-+/
 *       : : : : : : : : :
 *       +-+-+-+-+-+-+-+-+\
 *       |A|A|A|A|A|A|A|A| +------ v-coded goto address
 *       +-+-+-+-+-+-+-+-+/
 * </pre>
 */
final class CFSA2 extends FSA
{
    /**
     * Automaton header version value.
     */
    const VERSION = -58;

    /**
     * The target node of this arc follows the last arc of the current state
     * (no goto field).
     */
    const BIT_TARGET_NEXT = 1 << 7;

    /**
     * The arc is the last one from the current node's arcs list.
     */
    const BIT_LAST_ARC = 1 << 6;

    /**
     * The arc corresponds to the last character of a sequence
     * available when building the automaton (acceptor transition).
     */
    const BIT_FINAL_ARC = 1 << 5;

    /**
     * The count of bits assigned to storing an indexed label.
     */
    const LABEL_INDEX_BITS = 5;

    /**
     * Masks only the M bits of a flag byte.
     */
    const LABEL_INDEX_MASK = (1 << self::LABEL_INDEX_BITS) - 1;

    /**
     * Maximum size of the labels index.
     */
    const LABEL_INDEX_SIZE = (1 << self::LABEL_INDEX_BITS) - 1;

    /**
     * An array of bytes with the internal representation of the automaton.
     * Please see the documentation of this class for more information on how
     * this structure is organized.
     */
    public $arcs;

    /**
     * Flags for this automaton version.
     */
    private $flags;

    /**
     * Label mapping for M-indexed labels.
     */
    public $labelMapping;

    /**
     * If <code>true</code> states are prepended with numbers.
     */
    private $hasNumbers;

    /**
     * Epsilon node's offset.
     */
    private $epsilon = 0;

    /**
     * Reads an automaton from a byte stream.
     */
    public function __construct($stream)
    {
//        $in = $stream; //new DataInputStream($stream);

        // Read flags.
        $flagsPack = unpack('nflags', fread($stream, 2));
        $flagBits = $flagsPack['flags'];
        $this->flags = 0;
        foreach (FSAFlags::$values as $flag) {
            if (FSAFlags::isSet($flag, $flagBits)) {
                $this->flags |= $flag;
            }
        }

        if ($flagBits != $this->flags) {
            throw new IOException("Unrecognized flags: 0x" . dechex($flagBits));
        }

        $this->hasNumbers = FSAFlags::isSet(FSAFlags::NUMBERS, $this->flags);


        /*
         * Read mapping dictionary.
         */
        $mappingPack = unpack('cmappingSize', fgetc($stream));
        $labelMappingSize = $mappingPack['mappingSize'];
        var_dump('lms', $labelMappingSize);
        $this->labelMapping = [];
        $this->readFully($stream, $this->labelMapping, $labelMappingSize);

        /*
         * Read arcs' data.
         */
        error_log('read remaining');
        $timer = microtime(1);
        $this->arcs = $this->readRemaining($stream);
        error_log('read remaining: ' . round(microtime(1) - $timer, 4));
    }

    protected final function readFully($stream, &$b, int $len): void {
        $data = fread($stream, $len);
        $b = array_values(unpack('c*', $data));
        if (count($b) < $len) {
            throw new IOException();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNode(): int
    {
        // Skip dummy node marking terminating state.
        return $this->getDestinationNodeOffset($this->getFirstArc($this->epsilon));
    }

    /**
     * {@inheritDoc}
     */
    public final function getFirstArc(int $node): int
    {
        if ($this->hasNumbers) {
            return $this->skipVInt($node);
        } else {
            return $node;
        }
    }

    /**
     * {@inheritDoc}
     */
    public final function getNextArc(int $arc): int
    {
        if ($this->isArcLast($arc)) {
            return 0;
        } else {
            return $this->skipArc($arc);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getArc(int $node, int $label): int
    {
        for ($arc = $this->getFirstArc($node); $arc != 0; $arc = $this->getNextArc($arc)) {
            if ($this->getArcLabel($arc) == $label) {
                return $arc;
            }
        }

        // An arc labeled with "label" not found.
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndNode(int $arc): int
    {
        $nodeOffset = $this->getDestinationNodeOffset($arc);
//    assert nodeOffset != 0 : "Can't follow a terminal arc: " + arc;
        if (!($nodeOffset != 0)) {
            throw new \AssertionError("Can't follow a terminal arc: " . $arc);
        }
//    assert nodeOffset < arcs.length : "Node out of bounds.";
        if (!($nodeOffset < count($this->arcs))) {
            throw new \AssertionError("Node out of bounds.");
        }
        return $nodeOffset;
    }

    /**
     * {@inheritDoc}
     */
    public function getArcLabel(int $arc): int
    {
        $index = $this->arcs[$arc] & self::LABEL_INDEX_MASK;
        if ($index > 0) {
            return $this->labelMapping[$index];
        } else {
            return $this->arcs[$arc + 1];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRightLanguageCount(int $node): int
    {
//    assert getFlags().contains(FSAFlags.NUMBERS) : "This FSA was not compiled with NUMBERS.";
        if (!(FSAFlags::isSet(FSAFlags::NUMBERS, $this->getFlags()))) {
            throw new \AssertionError("This FSA was not compiled with NUMBERS.");
        }
        return $this->readVInt($this->arcs, $node);
    }

    /**
     * {@inheritDoc}
     */
    public function isArcFinal(int $arc): bool
    {
        return ($this->arcs[$arc] & self::BIT_FINAL_ARC) != 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isArcTerminal(int $arc): bool
    {
        return (0 == $this->getDestinationNodeOffset($arc));
    }

    /**
     * Returns <code>true</code> if this arc has <code>NEXT</code> bit set.
     *
     * @see #BIT_LAST_ARC
     *
     * @param int $arc The node's arc identifier.
     *
     * @return bool Returns true if the argument is the last arc of a node.
     */
    public function isArcLast(int $arc): bool
    {
        return ($this->arcs[$arc] & self::BIT_LAST_ARC) != 0;
    }

    /**
     * @see #BIT_TARGET_NEXT
     *
     * @param int $arc The node's arc identifier.
     *
     * @return bool Returns true if {@link #BIT_TARGET_NEXT} is set for this arc.
     */
    public function isNextSet(int $arc): bool
    {
        return ($this->arcs[$arc] & self::BIT_TARGET_NEXT) != 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Returns the address of the node pointed to by this arc.
     */
    protected function getDestinationNodeOffset(int $arc): int
    {
        if ($this->isNextSet($arc)) {
            /* Follow until the last arc of this state. */
            while (!$this->isArcLast($arc)) {
                $arc = $this->getNextArc($arc);
            }

            /* And return the byte right after it. */
            return $this->skipArc($arc);
        } else {
            /*
             * The destination node address is v-coded. v-code starts either
             * at the next byte (label indexed) or after the next byte (label explicit).
             */
            return $this->readVInt($this->arcs, $arc + (($this->arcs[$arc] & self::LABEL_INDEX_MASK) == 0 ? 2 : 1));
        }
    }

    /**
     * Read the arc's layout and skip as many bytes, as needed, to skip it.
     */
    private function skipArc(int $offset): int
    {
        $flag = $this->arcs[$offset++];

        // Explicit label?
        if (($flag & self::LABEL_INDEX_MASK) == 0) {
            $offset++;
        }

        // Explicit goto?
        if (($flag & self::BIT_TARGET_NEXT) == 0) {
            $offset = $this->skipVInt($offset);
        }

//    assert offset < this.arcs.length;
        if (!($offset < count($this->arcs))) {
            throw new \AssertionError('NOT: (offset < this.arcs.length)');
        }
        return $offset;
    }

    /**
     * Read a v-int.
     */
    protected static function readVInt($array, int $offset): int
    {
        $b = $array[$offset];
        $value = $b & 0x7F;

        for ($shift = 7; $b < 0; $shift += 7) {
            $b = $array[++$offset];
            $value |= ($b & 0x7F) << $shift;
        }

        return $value;
    }

    /**
     * Return the byte-length of a v-coded int.
     */
    protected static function vIntLength(int $value): int
    {
//    assert value >= 0 : "Can't v-code negative ints.";
        if (!($value >= 0)) {
            throw new \AssertionError("Can't v-code negative ints.");
        }

        for ($bytes = 1; $value >= 0x80; $bytes++) {
            $value >>= 7;
        }

        return $bytes;
    }

    /**
     * Skip a v-int.
     */
    private function skipVInt(int $offset)
    {
        while ($this->arcs[$offset++] < 0) {
            // Do nothing.
        }
        return $offset;
    }
}