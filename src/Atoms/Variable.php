<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

use RK\Proviso\Context;

class Variable implements DynamicAtom
{

    protected string $symbol;

    /**
     * @param string $symbol Used for both the look-up and original representation
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
        return self::TYPE_VAR;
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
        $value = $context->get($this->symbol);

        return new Value($value);
    }

}