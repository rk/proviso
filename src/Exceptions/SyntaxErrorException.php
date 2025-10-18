<?php
/**
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso\Exceptions;

class SyntaxErrorException extends \DomainException
{

    protected $original;
    protected $partial;

    /**
     * @param string $message
     * @param string $original
     * @param string $partial
     */
    public function __construct(string $message, string $original, string $partial)
    {
        $this->original = $original;
        $this->partial  = $partial;

        parent::__construct($message . "; near offset {$this->getOffset()} source: {$original}");
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function getPartial(): string
    {
        return $this->partial;
    }

    public function getOffset(): int
    {
        return strlen($this->original) - strlen($this->partial) - 1;
    }

}