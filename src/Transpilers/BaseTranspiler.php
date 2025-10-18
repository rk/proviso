<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Transpilers;

use RK\Proviso\Atoms\Atom;
use RK\Proviso\Atoms\Clause;
use RK\Proviso\Atoms\Group;
use RK\Proviso\Atoms\Operand;
use RK\Proviso\Atoms\Value;
use RK\Proviso\Atoms\Variable;
use RK\Proviso\Exceptions\TranspilationException;

abstract class BaseTranspiler
{

    public function transpile(Atom $atom): string
    {
        if ($atom instanceof Group) {
            return $this->translateGroup($atom);
        }

        if ($atom instanceof Clause) {
            return $this->translateClause($atom);
        }

        if ($atom instanceof Operand) {
            return $this->translateOperand($atom);
        }

        if ($atom instanceof Variable) {
            return $this->translateVariable($atom);
        }

        if ($atom instanceof Value) {
            return $this->translateValue($atom);
        }

        throw new TranspilationException('Cannot transpile ' . var_export($atom, true));
    }

    abstract public function translateGroup(Group $atom): string;

    abstract public function translateClause(Clause $atom): string;

    abstract public function translateOperand(Operand $atom): string;

    abstract public function translateVariable(Variable $atom): string;

    abstract public function translateValue(Value $atom): string;

}