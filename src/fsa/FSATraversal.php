<?php

namespace morfologik\fsa;


/**
 * This class implements some common matching and scanning operations on a
 * generic FSA.
 */
final class FSATraversal
{
    /**
     * Target automaton.
     * @var FSA
     */
    private $fsa;

    /**
     * Traversals of the given FSA.
     *
     * @param FSA $fsa The target automaton for traversals.
     */
    public function __construct(FSA $fsa)
    {
        $this->fsa = $fsa;
    }

    /**
     * Calculate perfect hash for a given input sequence of bytes. The perfect hash requires
     * that {@link FSA} is built with {@link FSAFlags#NUMBERS} and corresponds to the sequential
     * order of input sequences used at automaton construction time.
     *
     * @param sequence The byte sequence to calculate perfect hash for.
     * @param start Start index in the sequence array.
     * @param length Length of the byte sequence, must be at least 1.
     * @param node The node to start traversal from, typically the {@linkplain FSA#getRootNode() root node}.
     *
     * @return Returns a unique integer assigned to the input sequence in the automaton (reflecting
     * the number of that sequence in the input used to build the automaton). Returns a negative
     * integer if the input sequence was not part of the input from which the automaton was created.
     * The type of mismatch is a constant defined in {@link MatchResult}.
     */
    public function perfectHash($sequence, int $start = 0, int $length = null, int $node = null): int
    {
        if ($length === null) {
            $length = count($sequence);
        }
        if ($node === null) {
            $node = $this->fsa->getRootNode();
        }
//    assert $this->fsa->getFlags().contains(FSAFlags.NUMBERS) : "FSA not built with NUMBERS option.";
        if (!(FSAFlags::isSet(FSAFlags::NUMBERS, $this->fsa->getFlags()))) {
            throw new \AssertionError("FSA not built with NUMBERS option.");
        }
//        assert length > 0 : "Must be a non-empty sequence.";
        if (!($length > 0)) {
            throw new \AssertionError("Must be a non-empty sequence.");
        }

        $hash = 0;
        $end = $start + $length - 1;

        $seqIndex = $start;
        $label = $sequence[$seqIndex];

        // Seek through the current node's labels, looking for 'label', update hash.
        for ($arc = $this->fsa->getFirstArc($node); $arc != 0;) {
            if ($this->fsa->getArcLabel($arc) == $label) {
                if ($this->fsa->isArcFinal($arc)) {
                    if ($seqIndex == $end) {
                        return $hash;
                    }

                    $hash++;
                }

                if ($this->fsa->isArcTerminal($arc)) {
                    /* The automaton contains a prefix of the input sequence. */
                    return MatchResult::AUTOMATON_HAS_PREFIX;
                }

                // The sequence is a prefix of one of the sequences stored in the automaton.
                if ($seqIndex == $end) {
                    return MatchResult::SEQUENCE_IS_A_PREFIX;
                }

                // Make a transition along the arc, go the target node's first arc.
                $arc = $this->fsa->getFirstArc($this->fsa->getEndNode($arc));
                $label = $sequence[++$seqIndex];
                continue;
            } else {
                if ($this->fsa->isArcFinal($arc)) {
                    $hash++;
                }
                if (!$this->fsa->isArcTerminal($arc)) {
                    $hash += $this->fsa->getRightLanguageCount($this->fsa->getEndNode($arc));
                }
            }

            $arc = $this->fsa->getNextArc($arc);
        }

        // Labels of this node ended without a match on the sequence.
        // Perfect hash does not exist.
        return MatchResult::NO_MATCH;
    }

    /**
     * Finds a matching path in the dictionary for a given sequence of labels from
     * <code>sequence</code> and starting at node <code>node</code>.
     * Allows passing  a reusable {@link MatchResult} object so that no
     * intermediate garbage is  produced.
     *
     * @param MatchResult $reuse    The {@link MatchResult} to reuse.
     * @param array       $sequence Input sequence to look for in the automaton.
     * @param int         $start    Start index in the sequence array.
     * @param int         $length   Length of the byte sequence, must be at least 1.
     * @param int         $node     The node to start traversal from, typically the {@linkplain FSA#getRootNode() root node}.
     *
     * @return MatchResult The same object as <code>reuse</code>, but with updated match {@link MatchResult#kind}
     *        and other relevant fields.
     */
    public function match(MatchResult $reuse = null, $sequence, int $start = 0, int $length = null, int $node = null): MatchResult
    {
        $reuse = $reuse ?? new MatchResult();
        $length = $length ?? count($sequence);
        $node = $node ?? $this->fsa->getRootNode();

        if ($node == 0) {
            $reuse->reset(MatchResult::NO_MATCH, $start, $node);
            return $reuse;
        }

        $fsa = $this->fsa;
        $end = $start + $length;
        for ($i = $start; $i < $end; $i++) {
            $arc = $this->fsa->getArc($node, $sequence[$i]);
            if ($arc != 0) {
                if ($this->fsa->isArcFinal($arc) && $i + 1 == $end) {
                    /* The automaton has an exact match of the input sequence. */
                    $reuse->reset(MatchResult::EXACT_MATCH, $i, $node);
                    return $reuse;
                }

                if ($this->fsa->isArcTerminal($arc)) {
                    /* The automaton contains a prefix of the input sequence. */
                    $reuse->reset(MatchResult::AUTOMATON_HAS_PREFIX, $i + 1, 0);
                    return $reuse;
                }

                // Make a transition along the arc.
                $node = $this->fsa->getEndNode($arc);
            } else {
                $reuse->reset(MatchResult::NO_MATCH, $i, $node);
                return $reuse;
            }
        }

        /* The sequence is a prefix of at least one sequence in the automaton. */
        $reuse->reset(MatchResult::SEQUENCE_IS_A_PREFIX, 0, $node);
        return $reuse;
    }

}