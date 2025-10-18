<?php
/**
 * Copyright © 2020 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\Proviso;

use RK\Proviso\Atoms\Atom;
use RK\Proviso\Exceptions\SyntaxErrorException;

/**
 * @method static void set(string $key, $val)
 * @method static mixed get(string $key)
 * @method static void fill(array $vars)
 * @method static void def(string $symbol, \Closure $closure)
 */
class Proxy
{

    protected static $cache = [];

    /** @var Context */
    private static $context;
    /** @var Tokenizer */
    private static $tokenizer;
    /** @var Parser */
    private static $parser;

    public static function context(): Context
    {
        if (self::$context === null) {
            self::$context = Factory::makeContext();
        }

        return self::$context;
    }

    protected static function tokenizer(): Tokenizer
    {
        if (self::$tokenizer === null) {
            self::$tokenizer = Factory::makeTokenizer(self::context()->reservedWords());
        }

        return self::$tokenizer;
    }

    protected static function parser(): Parser
    {
        if (self::$parser === null) {
            self::$parser = new Parser(self::tokenizer());
        }

        return self::$parser;
    }

    /**
     * Tokenizes the source string.
     *
     * @param string $source
     * @return array
     */
    public static function tokenize(string $source): array
    {
        return self::tokenizer()->tokenize($source);
    }

    /**
     * Parses and returns the resulting Atom for the source.
     *
     * @param string $source
     * @return Atom
     */
    public static function parse(string $source): Atom
    {
        return self::parser()->parse($source);
    }

    /**
     * Parses the source, and returns any syntax errors without dying.
     *
     * @param string $source
     * @return string|null
     */
    public static function lint(string $source): ?string
    {
        try {
            self::parse($source);
        } catch (SyntaxErrorException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Compile the source and return an Expression that may be executed
     * independently. Uses caching to deduplicate repeated $source
     * strings.
     *
     * @param string $source
     * @return Expression
     */
    public static function compile(string $source): Expression
    {
        $cacheKey = md5($source);

        if (!isset(self::$cache[$cacheKey])) {
            $parser = self::parser();

            self::$cache[$cacheKey] = new Expression($parser->parse($source), self::context(), $parser->getLastDependencies());
        }

        return self::$cache[$cacheKey];
    }

    /**
     * Immediately compiles and executes the source; uses Proxy::compile()
     * to return a cache-deduplicated expression.
     *
     * @param string $source
     * @return mixed
     */
    public static function execute(string $source)
    {
        $compiled = self::compile($source);

        return $compiled();
    }

    public static function __callStatic(string $name, array $args)
    {
        if (in_array($name, ['set', 'get', 'fill', 'def'], true)) {
            return self::context()->$name(...$args);
        }

        throw new \BadMethodCallException("Unknown method {$name}");
    }

}