<?php

namespace morfologik\fsa;

use morfologik\exceptions\IOException;
use morfologik\exceptions\UnsupportedOperationException;
use ftIndex\util\automaton\BitSet;

/**
 * Class FSA
 *
 * @package morfologik\fsa
 * @author  Dmitrii Emelyanov <gilberg.vrn@gmail.com>
 * @date    9/30/19 8:12 PM
 */
abstract class FSA
{
    /**
     * @return Returns the identifier of the root node of this automaton. Returns
     *         0 if the start node is also the end node (the automaton is empty).
     */
    public abstract function getRootNode(): int;

  /**
   * @param node
   *          Identifier of the node.
   * @return Returns the identifier of the first arc leaving <code>node</code>
   *         or 0 if the node has no outgoing arcs.
   */
  public abstract function getFirstArc(int $node): int;

  /**
   * @param arc
   *          The arc's identifier.
   * @return Returns the identifier of the next arc after <code>arc</code> and
   *         leaving <code>node</code>. Zero is returned if no more arcs are
   *         available for the node.
   */
  public abstract function getNextArc(int $arc): int;

  /**
   * @param node
   *          Identifier of the node.
   * @param label
   *          The arc's label.
   * @return Returns the identifier of an arc leaving <code>node</code> and
   *         labeled with <code>label</code>. An identifier equal to 0 means the
   *         node has no outgoing arc labeled <code>label</code>.
   */
  public abstract function getArc(int $node, int $label): int;

  /**
   * @param arc
   *          The arc's identifier.
   * @return Return the label associated with a given <code>arc</code>.
   */
  public abstract function getArcLabel(int $arc): int;

  /**
   * @param arc
   *          The arc's identifier.
   * @return Returns <code>true</code> if the destination node at the end of
   *         this <code>arc</code> corresponds to an input sequence created when
   *         building this automaton.
   */
  public abstract function isArcFinal(int $arc): bool;

  /**
   * @param arc
   *          The arc's identifier.
   * @return Returns <code>true</code> if this <code>arc</code> does not have a
   *         terminating node (@link {@link #getEndNode(int)} will throw an
   *         exception). Implies {@link #isArcFinal(int)}.
   */
  public abstract function isArcTerminal(int $arc): bool;

  /**
   * @param arc
   *          The arc's identifier.
   * @return Return the end node pointed to by a given <code>arc</code>.
   *         Terminal arcs (those that point to a terminal state) have no end
   *         node representation and throw a runtime exception.
   */
  public abstract function getEndNode(int $arc): int;

  /**
   * @return Returns a set of flags for this FSA instance.
   */
  public abstract function getFlags(): int;

  /**
   * @param node
   *          Identifier of the node.
   * @return Calculates and returns the number of arcs of a given node.
   */
  public function getArcCount(int $node): int {
        $count = 0;
    for ($arc = $this->getFirstArc($node); $arc != 0; $arc = $this->getNextArc($arc)) {
            $count++;
        }
    return $count;
  }

  /**
   * @param node
   *          Identifier of the node.
   *
   * @return Returns the number of sequences reachable from the given state if
   *         the automaton was compiled with {@link FSAFlags#NUMBERS}. The size
   *         of the right language of the state, in other words.
   *
   * @throws UnsupportedOperationException
   *           If the automaton was not compiled with {@link FSAFlags#NUMBERS}.
   *           The value can then be computed by manual count of
   *           {@link #getSequences}.
   */
  public function getRightLanguageCount(int $node): int {
        throw new UnsupportedOperationException("Automaton not compiled with " . 'FSAFlags::NUMBERS');
    }

  /**
   * Returns an iterator over all binary sequences starting at the given FSA
   * state (node) and ending in final nodes. This corresponds to a set of
   * suffixes of a given prefix from all sequences stored in the automaton.
   *
   * <p>
   * The returned iterator is a {@link ByteBuffer} whose contents changes on
   * each call to {@link Iterator#next()}. The keep the contents between calls
   * to {@link Iterator#next()}, one must copy the buffer to some other
   * location.
   * </p>
   *
   * <p>
   * <b>Important.</b> It is guaranteed that the returned byte buffer is backed
   * by a byte array and that the content of the byte buffer starts at the
   * array's index 0.
   * </p>
   *
   * @param node
   *          Identifier of the starting node from which to return subsequences.
   * @return An iterable over all sequences encoded starting at the given node.
   */
  public function getSequences(int $node = null): array {
      if ($node === null) {
          $node = $this->getRootNode();
      }
        if ($node == 0) {
            return [];
    }

//        return new Iterable<ByteBuffer>() {
//      public Iterator<ByteBuffer> iterator() {
//        return new ByteSequenceIterator(FSA.this, node);
//      }
//    };
  }

  /**
   * Returns an iterator over all binary sequences starting from the initial FSA
   * state (node) and ending in final nodes. The returned iterator is a
   * {@link ByteBuffer} whose contents changes on each call to
   * {@link Iterator#next()}. The keep the contents between calls to
   * {@link Iterator#next()}, one must copy the buffer to some other location.
   *
   * <p>
   * <b>Important.</b> It is guaranteed that the returned byte buffer is backed
   * by a byte array and that the content of the byte buffer starts at the
   * array's index 0.
   * </p>
   */
//  public final Iterator<ByteBuffer> iterator() {
//    return getSequences().iterator();
//  }

  /**
   * Visit all states. The order of visiting is undefined. This method may be
   * faster than traversing the automaton in post or preorder since it can scan
   * states linearly. Returning false from {@link StateVisitor#accept(int)}
   * immediately terminates the traversal.
   *
   * @param v Visitor to receive traversal calls.
   * @param <T> A subclass of {@link StateVisitor}.
   * @return StateVisitor Returns the argument (for access to anonymous class fields).
   */
  public function visitAllStates(StateVisitor $v) {
        return $this->visitInPostOrder($v);
    }

  /**
   * Visits all states reachable from <code>node</code> in postorder. Returning
   * false from {@link StateVisitor#accept(int)} immediately terminates the
   * traversal.
   *
   * @param v Visitor to receive traversal calls.
   * @param <T> A subclass of {@link StateVisitor}.
   * @param node Identifier of the node.
   * @return StateVisitor Returns the argument (for access to anonymous class fields).
   */
  public function visitInPostOrder(StateVisitor $v, int $node = null) {

      if ($node === null) {
          $node = $this->getRootNode();
      }
        $this->visitInPostOrderRecursion($v, $node, new BitSet());
        return $v;
    }

  /** Private recursion. */
  private function visitInPostOrderRecursion(StateVisitor $v, int $node, BitSet $visited): bool {
        if ($visited->get($node)) {
            return true;
        }
        $visited->set($node);

        for ($arc = $this->getFirstArc($node); $arc != 0; $arc = $this->getNextArc($arc)) {
            if (!$this->isArcTerminal($arc)) {
                if (!$this->visitInPostOrderRecursion($v, $this->getEndNode($arc), $visited))
                    return false;
            }
        }

    return $v->accept($node);
  }

  /**
   * Visits all states in preorder. Returning false from
   * {@link StateVisitor#accept(int)} skips traversal of all sub-states of a
   * given state.
   *
   * @param v Visitor to receive traversal calls.
   * @param <T> A subclass of {@link StateVisitor}.
   * @param node Identifier of the node.
   * @return StateVisitor Returns the argument (for access to anonymous class fields).
   */
  public function visitInPreOrder(StateVisitor $v, int $node = null) {
      if ($node === null) {
          $node = $this->getRootNode();
      }
        $this->visitInPreOrderRecursion($v, $node, new BitSet());
        return $v;
    }

  /** Private recursion. */
  private function visitInPreOrderRecursion(StateVisitor $v, int $node, BitSet $visited) {
        if ($visited->get($node)) {
            return;
        }
        $visited->set($node);

        if ($v->accept($node)) {
            for ($arc = $this->getFirstArc($node); $arc != 0; $arc = $this->getNextArc($arc)) {
                if (!$this->isArcTerminal($arc)) {
                    $this->visitInPreOrderRecursion($v, $this->getEndNode($arc), $visited);
                }
            }
    }
    }

    /**
     * @param in The input stream.
     * @return array Reads all remaining bytes from an input stream and returns
     * them as a byte array.
     * @throws IOException Rethrown if an I/O exception occurs.
     */
    protected static final function readRemaining($in)
    {
        $output = [];
        while (!feof($in)) {
            foreach (unpack('c*', fread($in, 32767)) as $byte) {
                $output[] = $byte;
            }
        }

        return $output;
  }

  /**
   * A factory for reading automata in any of the supported versions.
   *
   * @param stream
   *          The input stream to read automaton data from. The stream is not
   *          closed.
   * @return Returns an instantiated automaton. Never null.
   * @throws IOException
   *           If the input stream does not represent an automaton or is
   *           otherwise invalid.
   */
  public static function read($stream): FSA {
        $header = FSAHeader::read($stream);

    switch ($header->version) {
        case FSA5::VERSION:
            return new FSA5($stream);
        case CFSA::VERSION:
            return new CFSA($stream);
        case CFSA2::VERSION:
            return new CFSA2($stream);
        default:
            throw new IOException(sprintf("Unsupported automaton version: 0x%02x", $header->version & 0xFF));
    }
  }

  /**
   * A factory for reading a specific FSA subclass, including proper casting.
   *
   * @param stream
   *          The input stream to read automaton data from. The stream is not
   *          closed.
   * @param clazz A subclass of {@link FSA} to cast the read automaton to.
   * @param <T> A subclass of {@link FSA} to cast the read automaton to.
   * @return Returns an instantiated automaton. Never null.
   * @throws IOException
   *           If the input stream does not represent an automaton, is otherwise
   *           invalid or the class of the automaton read from the input stream
   *           is not assignable to <code>clazz</code>.
   */
//  public static <T extends FSA> T read(InputStream stream, Class<? extends T> clazz) throws IOException {
//        FSA fsa = read(stream);
//    if (!clazz.isInstance(fsa)) {
//        throw new IOException(String.format(Locale.ROOT, "Expected FSA type %s, but read an incompatible type %s.",
//                clazz.getName(), fsa.getClass().getName()));
//    }
//    return clazz.cast(fsa);
//  }
}