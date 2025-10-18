<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

use RK\Proviso\Context;

class Operand implements DynamicAtom
{

    /** @var string */
    protected $symbol;

    /**
     * @param string $symbol
     */
    public function __construct(string $symbol)
    {
        $this->symbol = $symbol;
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return self::TYPE_EXPR;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->symbol;
    }

    /**
     * @inheritDoc
     */
    public function result(Context $context): Value
    {
        throw new \RuntimeException('Cannot call result() on an Operand; special case meant for Clause(es)');
    }

    public function eval(Context $context, Atom ...$atoms): Value
    {
        $cb = $context->operandFor($this->symbol);

        // Reduce all nested atoms into their result/value
        $params = array_map(static function (Atom $atom) use ($context) {
            return $atom instanceof DynamicAtom
                ? $atom->result($context)->value()
                : $atom->value();
        }, $atoms);

        // Execute the closure on the params...
        $value = $cb(...$params);

        if (!is_bool($value) && $value !== null) {
            $value = var_export($value, true);
        }

        return new Value($value);
    }

    public function isConjunction(): bool
    {
        return $this->symbol === 'OR' || $this->symbol === 'AND';
    }

}