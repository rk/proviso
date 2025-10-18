<?php
/**
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso;

use RK\Proviso\Atoms\Atom;
use RK\Proviso\Atoms\Clause;
use RK\Proviso\Atoms\Group;
use RK\Proviso\Atoms\Operand;
use RK\Proviso\Atoms\Value;
use RK\Proviso\Atoms\Variable;
use BadMethodCallException;
use function in_array;
use function preg_match;

/**
 * @method static Clause makeClause(Atom $left, Operand $op, Atom $right)
 * @method static Group makeGroup(Operand $op, Atom ...$atoms)
 * @method static Operand makeOperand(string $symbol)
 * @method static Value makeValue($value)
 * @method static Variable makeVariable(string $symbol)
 */
final class Factory
{

    protected const ATOMS = [
        'Clause',
        'Group',
        'Operand',
        'Value',
        'Variable',
    ];

    public static function makeContext(): Context
    {
        $ctx = new Context();

        $ctx->def('AND', static function (...$array) {
            // Shorten evaluation by returning false on the first false-y item
            foreach ($array as $item) {
                if (!$item) {
                    return false;
                }
            }

            return true;
        });

        $ctx->def('OR', static function (...$array) {
            // Shorten evaluation by returning true on the first truthy item
            foreach ($array as $item) {
                if ($item) {
                    return true;
                }
            }

            return false;
        });

        $ctx->def('IN', static function ($value, $array) {
            return in_array($value, $array, true);
        });

        $ctx->def('NOT IN', static function ($value, $array) {
            return !in_array($value, $array, true);
        });

        $ctx->def('=', static fn ($a, $b) => $a == $b);
        $ctx->def('!=', static fn ($a, $b) => $a != $b);
        $ctx->def('>=', static fn ($a, $b) => $a >= $b);
        $ctx->def('<=', static fn ($a, $b) => $a <= $b);

        return $ctx;
    }

    public static function makeTokenizer(): Tokenizer
    {
        return new Tokenizer();
    }

    public static function makeParser(Tokenizer $tokenizer): Parser
    {
        return new Parser($tokenizer);
    }

    public static function __callStatic(string $name, array $args)
    {
        if (preg_match('/^make([A-Z][a-z]+)$/', $name, $m) && in_array($m[1], self::ATOMS, true)) {
            $class = '\\RK\\Proviso\\Atoms\\' . $m[1];

            return new $class(...$args);
        }

        throw new BadMethodCallException("Unknown method {$name}");
    }

}