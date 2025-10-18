<?php
/**
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso;

use RK\Proviso\Atoms\Atom;
use RK\Proviso\Atoms\DynamicAtom;
use RK\Proviso\Atoms\StaticAtom;
use RK\Proviso\Exceptions\TranspilationException;

class Expression
{

    /** @var Transpilers\JsTranspiler */
    protected static $transpileJs;
    /** @var Transpilers\PhpTranspiler */
    protected static $transpilePhp;

    /** @var StaticAtom|DynamicAtom */
    protected $atom;
    /** @var Context */
    protected $context;
    /** @var array */
    protected $dependencies;
    /** @var string|null */
    protected $phpCache;

    /**
     * @param Atom $atom
     * @param Context $context
     * @param array $dependencies Names of fields this one depends on.
     */
    public function __construct(Atom $atom, Context $context, array $dependencies)
    {
        $this->atom         = $atom;
        $this->context      = $context;
        $this->dependencies = $dependencies;
    }

    public function setContext(Context $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function toJavaScript(): string
    {
        if (empty(static::$transpileJs)) {
            static::$transpileJs = new Transpilers\JsTranspiler();
        }

        return static::$transpileJs->transpile($this->atom);
    }

    public function toPHP(): string
    {
        if (empty(static::$transpilePhp)) {
            static::$transpilePhp = new Transpilers\PhpTranspiler();
        }

        if ($this->phpCache === null) {
            $this->phpCache = "return (bool)(" . static::$transpilePhp->transpile($this->atom) . ");";
        }

        return $this->phpCache;
    }

    public function value()
    {
        return $this->atom instanceof DynamicAtom
            ? $this->atom->result($this->context)->value()
            : $this->atom->value();
    }

    public function eval(): bool
    {
        // Used by the evaluated code to fetch variable values.
        $context = $this->context;

        try {
            $result = eval($this->toPHP());
        } catch (\ParseError $e) {
            throw new TranspilationException("Syntax error in expression output:\n\n{$this->toPHP()}", 0, $e);
        }

        return $result;
    }

    public function __sleep(): array
    {
        return ['atom', 'dependencies'];
    }

    public function __wakeup()
    {
        $this->context = \Proviso::context();
    }

    public function __toString()
    {
        return (string)$this->atom;
    }

    public function __invoke()
    {
        return $this->eval();
    }

}