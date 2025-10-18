<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

use RK\Proviso\Context;

class Group implements GroupingAtom
{

    /** @var Operand */
    protected $operand;
    /** @var Atom[] */
    protected $atoms;

    public function __construct(Operand $op, Atom ...$atoms)
    {
        $this->operand = $op;
        $this->atoms   = $atoms;
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return self::TYPE_EXPR;
    }

    public function getOperand(): Operand
    {
        return $this->operand;
    }

    /**
     * @return Atom[]
     */
    public function getAtoms(): array
    {
        return $this->atoms;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return '(' . implode(' ' . $this->operand . ' ', $this->atoms) . ')';
    }

    /**
     * @inheritDoc
     */
    public function result(Context $context): Value
    {
        return $this->operand->eval($context, ...$this->atoms);
    }

}