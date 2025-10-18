<?php
/**
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso;

use RK\Proviso\Atoms\Atom;
use RK\Proviso\Atoms\DynamicAtom;
use RK\Proviso\Atoms\Operand;
use RK\Proviso\Atoms\StaticAtom;
use RK\Proviso\Atoms\Value;
use RK\Proviso\Atoms\Variable;
use RK\Proviso\Token;
use RK\Proviso\Exceptions\SyntaxErrorException;
use function array_filter;
use function array_map;
use function array_shift;
use function array_slice;
use function array_splice;
use function array_unique;
use function array_values;
use function count;
use function is_array;
use function trim;

class Parser
{

    /** @var Tokenizer */
    protected Tokenizer $tokenizer;
    /** @var Variable[] */
    protected array $dependencies;

    protected static $time = 0;
    protected static $total = 0;

    public function __construct(Tokenizer $tokenizer) {
        $this->tokenizer = $tokenizer;
    }

    public static function getTotalTime(): float
    {
        return self::$time * 1000;
    }

    public static function getItemizedTime(): float
    {
        return self::$total
            ? (self::$time * 1000) / self::$total
            : 0;
    }

    public function parse(string $source): DynamicAtom
    {
        $this->dependencies = [];

        if (trim($source) === '') {
            throw new SyntaxErrorException('Cannot parse an empty string.', $source, $source);
        }

        // Tokenize the input
        $tokens = $this->tokenizer->tokenize($source);

        // Time parsing separately from tokenization
        $start = microtime(true);

        // remove all formatting-only/useless
        $tokens = array_values(array_filter($tokens, static function ($token) {
            // Remove boundaries that aren't ( or )
            if ($token['type'] === Token::Bounds) {
                return $token['text'] === '(' || $token['text'] === ')';
            }

            return $token['type'] !== Token::Whitespace;
        }));

        // Remap the tokens into basic atoms, before we apply grouping
        $this->compileBasicAtoms($tokens);
        // Then find patterns that create lists in MySQL
        $this->compileLists($tokens);
        // Compile IN/NOT-IN into clauses
        $this->compileNonConjunctionClauses($tokens);
        // Consolidate the remaining atoms till there is 1 left
        $this->consolidateRemainder($tokens);

        self::$time  += microtime(true) - $start;
        self::$total += 1;

        return $tokens[0];
    }

    public function getLastDependencies(): array
    {
        return array_keys($this->dependencies);
    }

    protected function compileBasicAtoms(array &$tokens): void
    {
        foreach ($tokens as $index => $token) {
            switch ($token['type']) {
                case Token::Variable:
                    $tokens[$index] = Factory::makeVariable($token['text']);
                    $this->dependencies[$token['text']] = true;
                    break;

                case Token::String:
                    $tokens[$index] = Factory::makeValue(substr($token['text'], 1, -1));
                    break;

                case Token::Conjunction:
                case Token::Operator:
                    $tokens[$index] = Factory::makeOperand($token['text']);
                    break;
            }
        }
    }

    protected function compileLists(array &$tokens): void
    {
        do {
            $index = 0;
            $count = count($tokens);

            while ($index < $count) {
                // We're looking for grouping characters, so skip this...
                if ($tokens[$index] instanceof Atom) {
                    $index++;
                    continue;
                }

                // We found our opening parenthesis; now check if it's a list...
                if ($tokens[$index]['type'] === Token::Bounds && $tokens[$index]['text'] === '(') {
                    $stop = $index + 1;

                    // Find the next non-Value token and mark it as the list
                    // ending token.
                    while (isset($tokens[$stop]) && $tokens[$stop] instanceof Value) {
                        $stop++;
                    }

                    // If we ended on a closing group character, then we found a
                    // valid list.
                    if (is_array($tokens[$stop]) && $tokens[$stop]['type'] === Token::Bounds && $tokens[$stop]['text'] === ')') {
                        // Transform the Values into a PHP array.
                        $values = array_map(static function (StaticAtom $value) {
                            return $value->value();
                        }, array_slice($tokens, $index + 1, $stop - $index - 1));

                        // Replace the boundary and value tokens with the new
                        // list value.
                        array_splice($tokens, $index, $stop - $index + 1, [
                            Factory::makeValue($values),
                        ]);

                        // Because the array is altered and reindexed, we must
                        // update the count and step to the immediate next index
                        // instead of the $stop index. Otherwise, it will skip
                        // other groups.
                        $count = count($tokens);
                        $index++;
                        continue;
                    }

                    // When it's a non-match, continue the scan at the stop index.
                    $index = $stop;
                    continue;
                }

                $index++;
            }
        } while ($index + 1 < $count);
    }

    protected function compileNonConjunctionClauses(array &$tokens): void
    {
        $index = 0;

        while (($index = $this->seekNextNonConjunctionClause($tokens, $index)) > -1) {
            $clause = Factory::makeClause($tokens[$index], $tokens[$index + 1], $tokens[$index + 2]);
            array_splice($tokens, $index, 3, [$clause]);

            // Continue search, beginning AFTER the last built clause.
            $index += 1;
        }
    }

    protected function seekNextNonConjunctionClause(array $tokens, int $index = 0, int $dir = 1): int
    {
        for ($i = $index; isset($tokens[$i]); $i += $dir) {
            if (
                // Check bounds first
                isset($tokens[$i + 1], $tokens[$i + 2])
                // Ensure pattern of: Variable, Operand, Value
                && $tokens[$i] instanceof Variable
                && $tokens[$i + 1] instanceof Operand
                && $tokens[$i + 2] instanceof Value
                // And ensure the Operand is not a conjunction
                && !$tokens[$i + 1]->isConjunction()
            ) {
                return $i;
            }
        }

        return -1;
    }

    protected function consolidateRemainder(array &$tokens): void
    {
        // While there is still an open parenthesis...
        while (count($tokens) > 1) {
            $result = $this->findNextInnerParens($tokens);

            if ($result !== null) {
                [$from, $to] = $result;
                $group = $this->compileGroup(array_slice($tokens, $from + 1, $to - $from - 1));
                array_splice($tokens, $from, $to - $from + 1, [$group]);
                continue;
            }

            // If we can't find a group, assume the top-level is in the format
            // of: (THIS OP THAT OP THAT OP ...).
            $tokens = [$this->compileGroup($tokens)];
            break;
        }
    }

    protected function findNextInnerParens(array $tokens): ?array
    {
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token['type'] === Token::Bounds) {
                if ($token['text'] === '(') {
                    $lastOpen = $index;
                }

                // Return a pair of open/close parens; by now, non-grouping
                // parens should be eliminated.
                if (isset($lastOpen) && $token['text'] === ')') {
                    return [$lastOpen, $index];
                }
            }
        }

        return null;
    }

    protected function compileGroup(array $group): Atom
    {
        // Accepts a range of things, and then returns an Atom representing the
        // group. Possibly a Group or Clause return type.

        if (
            count($group) === 3
            && $group[0] instanceof Atom
            && $group[1] instanceof Operand
            && $group[2] instanceof Atom
        ) {
            return Factory::makeClause(...$group);
        }

        $operands = array_values(array_filter($group, static function ($atom) {
            return $atom instanceof Operand;
        }));

        // Need to verify if there are multiple kinds of conjunction or only one
        // (a uniform group).
        $same = count(array_unique($operands, SORT_STRING)) === 1;

        if ($same) {
            return Factory::makeGroup($operands[0], ...array_filter($group, static function ($atom) {
                return !$atom instanceof Operand;
            }));
        }

        // Non-uniform groups need to group from left to right. By that, this
        //   1 | 1 | 1 & 1
        // becomes:
        //   (((1 | 1) | 1) & 1)
        $left = array_shift($group);

        while (count($group) >= 2) {
            $left = Factory::makeClause($left, array_shift($group), array_shift($group));
        }

        return $left;
    }

}