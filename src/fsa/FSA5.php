<?php

namespace morfologik\fsa;

/**
 * Class FSA5
 *
 * @package morfologik\fsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    10/1/19 7:14 PM
 */
class FSA5
    extends FSA
{
    /**
     * Automaton header version value.
     */
    const VERSION = 5;

    /**
     * @return Returns the identifier of the root node of this automaton. Returns
     *         0 if the start node is also the end node (the automaton is empty).
     */
    public function getRootNode(): int
    {
        // TODO: Implement getRootNode() method.
    }

    /**
     * @param node
     *          Identifier of the node.
     *
     * @return Returns the identifier of the first arc leaving <code>node</code>
     *         or 0 if the node has no outgoing arcs.
     */
    public function getFirstArc(int $node): int
    {
        // TODO: Implement getFirstArc() method.
    }

    /**
     * @param arc
     *          The arc's identifier.
     *
     * @return Returns the identifier of the next arc after <code>arc</code> and
     *         leaving <code>node</code>. Zero is returned if no more arcs are
     *         available for the node.
     */
    public function getNextArc(int $arc): int
    {
        // TODO: Implement getNextArc() method.
    }

    /**
     * @param node
     *          Identifier of the node.
     * @param label
     *          The arc's label.
     *
     * @return Returns the identifier of an arc leaving <code>node</code> and
     *         labeled with <code>label</code>. An identifier equal to 0 means the
     *         node has no outgoing arc labeled <code>label</code>.
     */
    public function getArc(int $node, int $label): int
    {
        // TODO: Implement getArc() method.
    }

    /**
     * @param arc
     *          The arc's identifier.
     *
     * @return Return the label associated with a given <code>arc</code>.
     */
    public function getArcLabel(int $arc): int
    {
        // TODO: Implement getArcLabel() method.
    }

    /**
     * @param arc
     *          The arc's identifier.
     *
     * @return Returns <code>true</code> if the destination node at the end of
     *         this <code>arc</code> corresponds to an input sequence created when
     *         building this automaton.
     */
    public function isArcFinal(int $arc): bool
    {
        // TODO: Implement isArcFinal() method.
    }

    /**
     * @param arc
     *          The arc's identifier.
     *
     * @return Returns <code>true</code> if this <code>arc</code> does not have a
     *         terminating node (@link {@link #getEndNode(int)} will throw an
     *         exception). Implies {@link #isArcFinal(int)}.
     */
    public function isArcTerminal(int $arc): bool
    {
        // TODO: Implement isArcTerminal() method.
    }

    /**
     * @param arc
     *          The arc's identifier.
     *
     * @return Return the end node pointed to by a given <code>arc</code>.
     *         Terminal arcs (those that point to a terminal state) have no end
     *         node representation and throw a runtime exception.
     */
    public function getEndNode(int $arc): int
    {
        // TODO: Implement getEndNode() method.
    }

    /**
     * @return Returns a set of flags for this FSA instance.
     */
    public function getFlags(): int
    {
        // TODO: Implement getFlags() method.
}}