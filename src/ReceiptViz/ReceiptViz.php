<?php

namespace Dcentrica\Chainpoint\Graphviz;

use \Exception;

/**
 * @author  Russell Michell <russ@theruss.com> 2018
 * @package dcentrica-chainpoint-tools
 * @license BSD-3
 *
 * Works with v3 Chainpoint Receipts and Graphviz libraries to produce visual representations of chainpoint data
 * in any format supported by Graphviz itself.
 */
class ReceiptViz
{
    /**
     * @var string
     */
    protected $chain = 'bitcoin';

    /**
     * @var string
     */
    protected $receipt = '';

    /**
     * @param  string $proof The Chainpoint Proof JSON document as a JSON string.
     */
    public function __construct(string $receipt = '', string $chain = '')
    {
        if (!self::which('dot')) {
            throw new Exception('Graphviz dot program not available!');
        }

	$this->setReceipt($receipt);
	$this->setChain($chain);
    }

    /**
     * Set the current valid blockchain network for working with.
     * 
     * @param  string $chain e.g. 'bitcoin'
     * @return ReceiptViz
     */
    public function setChain(string $chain) : ReceiptViz
    {
        $this->chain = $chain;

        return $this;
    }

    /**
     * Set the current Chainpoint receipt for working with.
     * 
     * @param  string $receipt
     * @return ReceiptViz
     */
    public function setReceipt(string $receipt) : ReceiptViz
    {
        $this->receipt = $chain;

        return $this;
    }

    /**
     * @return string The current chainpoint receipt
     */
    public function getReceipt() : string
    {
        return $this->receipt;
    }

    /**
     * @return string The current chain
     */
    public function getChain() : string
    {
        return $this->chain;
    }

    /**
     * Generate a dot file for consumption by the GraphViz "dot" utility. The dot file is then used by dot to generate graphical representations
     * of a chainpoint proof in almost any image format.
     *
     * @param  string $hash  The "local" hash from which a Merkle Proof is generated.
     * @param  string $root  The Merkle Root hash as stored on a blockchain.
     * @return string        A stringy representation of a dotfile for use by Graphviz.
     * @throws Exception
     */
    public function toDot(string $hash, string $root) : string
    {
	// Prepare a dot file template
        $dotTpl = 'digraph G { node [shape = record] node0 [ label =\"<f0> | <f1> $root | <f2> \"]; %s %s }';

	// Let's get and process the "ops" array
	$getOps = sprintf('get%sOps', $this->getChain());
        $currHashVal = $hash;
        $dotFileArr = ['s1' => [], 's2' => []];
        $i = 0;

        // Assumes hex data
        foreach ($this->$getOps() as $op => $val) {
            $op = key($val);
            $val = current($val);
            $hasNext = !empty($ops[$i+1]);
            $isLast = $i+1 === sizeof($ops);

            for ($j = 1; $j <= 2; $j++) {
                if ($op === 'r') {
                    $currHashVal = self::hash($ops[$i+1]['op'], $currHashVal . $val);
                } else if ($op === 'l') {
                    $currHashVal = self::hash($ops[$i+1]['op'], $val . $currHashVal);
                }

                $isSection1 = ($j === 1);
                $isSection2 = ($j === 2);
                $currNodeIdx = $i;
                $nextNodeIdx = $isLast ? $i+2 : $i+1;

		// Section 1 defines the construction of the dotfile node definitions
                if ($isSection1) {
                    $dotFileArr['s1'][] = "node$currNodeIdx [ label =\"<f0> | <f1> $currHashVal | <f2> \"]; ";
		// Section 2 defines the relation of each node to one another
                } else if ($isSection2) {
                    if (!$isLast) {
                        $dotFileArr['s2'][] = "\"node$currNodeIdx\":f1 -> \"node$nextNodeIdx\":f1; ";
                    }
                }
            }

            // Push the merkle root onto the end
            if ($isLast) {
                $val = "\"node$currNodeIdx\":f1 -> \"node$nextNodeIdx\":f1;";
                array_push($dotFileArr['s2'], $root);
            }

            ++$i;
        }

        // Assemble the pieces
        $return sprintf(
	    $dotTpl,
	    implode(' ', $dotFileArr['s1']),
	    implode(' ', $dotFileArr['s2'])
	)
    }

    /**
     * Gets the bitcoin ops array from the current receipt.
     *
     * @return array
     * @throws Exception
     */
    public function getBitcoinOps() : array
    {
        $receipt = json_decode($this->getReceipt(), true);

	if (!isset($branch = $receipt['branches']['branches'])) {
	    throw new Exception('Invalid receipt! Sub branches not found.');
	}

	if (!isset($branch['label']) || $branch['label'] !== 'btc') {
	    throw new Exception('Invalid receipt! "btc" sub-branch not found.');
        }

	if (empty($branch['ops'])) {
	    throw new Exception('Invalid receipt! "btc" ops data not found.');
        }

        return $branch['ops'];
    }

    /**
     *
     * @param  string $cmd
     * @return bool
     */
    public static function which(string $cmd) : bool
    {
        $output = [];
        $return = 0;

        exec("which $cmd", $output, $return);

	return $return === 0;
    }

    /**
     * Simple hashing in a chainpoint receipt context. 
     *
     * @param  string $func The hash function to use.
     * @param  string $subj The string to hash.
     * @return string       The hashed string.
     */
    public function hash(string $func, string $subj) : string
    {
	$parts = explode('-', $func);
	$func = "{$parts[0]}{$parts[1]}";

        if (count($parts) === 3) {
            return hash($func, hash($func, $subj));
        }

       return hash($func, $subj);
    }

}

