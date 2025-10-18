<?php
/*
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Atoms;

use RK\Proviso\Context;

interface DynamicAtom extends Atom
{

    /**
     * Returns the evaluated Value of the atom.
     *
     * @param Context $context
     * @return Value
     */
    public function result(Context $context): Value;

}