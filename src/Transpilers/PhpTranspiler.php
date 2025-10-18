<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Transpilers;

use RK\Proviso\Atoms\Clause;
use RK\Proviso\Atoms\Group;
use RK\Proviso\Atoms\Operand;
use RK\Proviso\Atoms\Value;
use RK\Proviso\Atoms\Variable;
use function array_map;
use function implode;
use function var_export;

class PhpTranspiler extends BaseTranspiler
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
        $left  = $this->transpile($atom->getFirst());
        $op    = $this->translateOperand($atom->getOperand());
        $right = $this->transpile($atom->getSecond());

        switch ($op) {
            case 'IN':
                return "in_array({$left}, {$right}, true)";

            case 'NOT IN':
                return "!in_array({$left}, {$right}, true)";
        }

        return "({$left} {$op} {$right})";
    }

    public function translateOperand(Operand $atom): string
    {
        return self::OPERAND_MATRIX[$atom->getSymbol()] ?? $atom->getSymbol();
    }

    public function translateVariable(Variable $atom): string
    {
        return "\$context->get('{$atom->getSymbol()}')";
    }

    public function translateValue(Value $atom): string
    {
        return var_export($atom->value(), true);
    }
}