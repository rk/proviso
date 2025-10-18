<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso;

class Context
{

    /** @var array */
    protected $vars = [];

    /** @var array */
    protected $funcs = [];

    public function set(string $key, $val): void
    {
        $this->vars[$key] = $val;
    }

    public function get(string $key)
    {
        return $this->vars[$key] ?? null;
    }

    public function fill(array $vars): void
    {
        $this->vars = array_replace($this->vars, $vars);
    }

    public function def(string $symbol, \Closure $closure): void
    {
        $this->funcs[$symbol] = $closure;
    }

    public function isSymbol(string $symbol): bool
    {
        return isset($this->funcs[$symbol]);
    }

    public function operandFor(string $symbol): ?\Closure
    {
        return $this->funcs[$symbol] ?? null;
    }

    public function reservedWords(): array
    {
        return array_keys($this->funcs);
    }

}