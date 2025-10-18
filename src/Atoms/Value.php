<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

class Value implements StaticAtom
{

    protected mixed $value;

    /**
     * @param mixed $value The PHP scalar value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return self::TYPE_VAL;
    }

    /**
     * @inheritDoc
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        switch (gettype($this->value)) {
            case 'array':
                $result = '(';

                foreach ($this->value as $value) {
                    $result .= var_export($value, true) . ', ';
                }

                return rtrim($result, ', ') . ')';

            case 'string':
                return '"' . $this->value . '"';

            case 'boolean':
                return $this->value ? 'TRUE' : 'FALSE';

            case 'NULL':
                return 'NULL';
        }

        return (string)$this->value;
    }

}