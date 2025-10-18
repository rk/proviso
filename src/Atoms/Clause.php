<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

use RK\Proviso\Context;

class Clause implements GroupingAtom
{

    /** @var Atom */
    protected $first;
    /** @var Operand */
    protected $operand;
    /** @var Atom */
    protected $second;

    public function __construct(Atom $first, Operand $op, Atom $second)
    {
        $this->first   = $first;
        $this->operand = $op;
        $this->second  = $second;
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return self::TYPE_EXPR;
    }

    public function getFirst(): Atom
    {
        return $this->first;
    }

    public function getOperand(): Operand
    {
        return $this->operand;
    }

    public function getSecond(): Atom
    {
        return $this->second;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return "({$this->first} {$this->operand} {$this->second})";
    }

    /**
     * @inheritDoc
     */
    public function result(Context $context): Value
    {
        return $this->operand->eval($context, $this->first, $this->second);
    }

}