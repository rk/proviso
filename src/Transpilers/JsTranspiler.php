<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Transpilers;

use RK\Proviso\Atoms\Clause;
use RK\Proviso\Atoms\Group;
use RK\Proviso\Atoms\Operand;
use RK\Proviso\Atoms\StaticAtom;
use RK\Proviso\Atoms\Value;
use RK\Proviso\Atoms\Variable;
use RK\Proviso\Exceptions\TranspilationException;
use function array_map;
use function count;
use function implode;
use function is_array;
use function json_encode;

class JsTranspiler extends BaseTranspiler
{

    public const OPERAND_MATRIX = [
        'AND' => '&&',
        'OR'  => '||',
    ];

    public function translateGroup(Group $atom): string
    {
        $op    = $this->translateOperand($atom->getOperand());
        $parts = array_map([$this, 'transpile'], $atom->getAtoms());

        return '(' . implode(" {$op} ", $parts) . ')';
    }

    public function translateClause(Clause $atom): string
    {
        $singleVal = $atom->getSecond() instanceof StaticAtom
            && is_array($atom->getSecond()->value())
            && count($atom->getSecond()->value()) === 1;

        $left  = $this->transpile($atom->getFirst());
        $right = $singleVal
            ? json_encode($atom->getSecond()->value()[0])
            : $this->transpile($atom->getSecond());
        $op    = $this->translateOperand($atom->getOperand());

        if (!$atom->getSecond() instanceof StaticAtom || $atom->getOperand()->isConjunction()) {
            return "({$left} {$op} {$right})";
        }

        switch ($op) {
            case 'IN':
                return $singleVal
                    ? "{$left} === {$right}"
                    : "{$right}.indexOf({$left}) !== -1";

            case 'NOT IN':
                return $singleVal
                    ? "{$left} !== {$right}"
                    : "{$right}.indexOf({$left}) === -1";
        }

        throw new TranspilationException("Cannot transpile {$op} into a JavaScript operation");
    }

    public function translateOperand(Operand $atom): string
    {
        return self::OPERAND_MATRIX[$atom->getSymbol()] ?? $atom->getSymbol();
    }

    public function translateVariable(Variable $atom): string
    {
        return "Context.{$atom->getSymbol()}";
    }

    public function translateValue(Value $atom): string
    {
        return json_encode($atom->value());
    }
}