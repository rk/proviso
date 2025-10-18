<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

interface Atom
{

    // Listed in evaluation priority (first to last), are the type of ATOMs
    // supported. NIL / VAL are considered static; VAR / EXPR are dynamic.
    // We actually don't care about data types, as all data types can be
    // encapsulated as VAR for PHP...
    public const TYPE_VAL  = 1;
    public const TYPE_VAR  = 2;
    public const TYPE_EXPR = 3;

    /**
     * Returns the type enum for the atom.
     *
     * @return int
     */
    public function getType(): int;

    /**
     * We require the toString conversion method, so that the expression may
     * be reconstructed.
     *
     * @return string
     */
    public function __toString();

}