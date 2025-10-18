<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

interface StaticAtom extends Atom
{

    /**
     * Returns the value of the atom.
     *
     * @return mixed
     */
    public function value();

}