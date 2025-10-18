<?php

namespace RK\Proviso;

enum Token: string
{

    case Whitespace = 'space';
    case Variable = 'var';
    case String = 'string';
    case Numeric = 'numeric';
    case Boolean = 'boolean';
    case Null = 'null';
    case Bounds = 'bounds';
    case Operator = 'operator';
    case Conjunction = 'conjunction';

}