<?php declare(strict_types=1);

namespace RK\Proviso;

use RK\Proviso\Exceptions\SyntaxErrorException;
use RK\Proviso\Token;
use function array_fill;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function microtime;
use function preg_match;
use function strlen;
use function strtoupper;
use function substr;

class Tokenizer
{

    /**
     * I'm lazy, and splitting out each group makes it easier for me during
     * development. Compiling into a singular pattern isn't slow, and the
     * instance will be kept as a singleton during the request lifetime.
     */
    protected const PATTERNS = [
        'space'       => '[\s\r\n]+',
        'bounds'      => '[,;()\[\]]',
        'numeric'     => '[-+]?\d+(?:\.\d+)?',
        'boolean'     => '(?i:true|false)',
        'null'        => '(?i:null)',
        'conjunction' => '(?i:or|and)',
        'operator'    => '(?i:not in|in|[<>!]=)',
        'var'         => '[A-Za-z][_\w]+(?:\.[A-Za-z][_\w]+)*',
        'string'      => '(["\'])(?:\\.|(?!\g-1).)*+\g-1',
    ];

    protected string $pattern;

    // Performance Metrics
    protected static float $time  = 0;
    protected static int $total = 0;

    public function __construct()
    {
        $patternParts = array_map(
            static fn ($pattern, $key) => "?<{$key}>{$pattern}",
            array_values(self::PATTERNS),
            array_keys(self::PATTERNS),
        );

        $this->pattern = '/^(?:(' . implode(')|(', $patternParts) . '))/';
    }

    public static function getTotalTime(): float
    {
        return self::$time * 1000;
    }

    public static function getTotalCount(): int
    {
        return self::$total;
    }

    public static function getItemizedTime(): float
    {
        return self::$total
            ? (self::$time * 1000) / self::$total
            : 0;
    }

    public function tokenize(string $source): array
    {
        $original = $source;
        $tokens   = [];
        $start    = microtime(true);

        while ($source !== '') {
            if (!preg_match($this->pattern, $source, $match)) {
                throw new SyntaxErrorException('Syntax error: unexpected or unsupported syntax', $original, $source);
            }

            // Eliminate numeric capture groups. Only named groups will be used
            // for tokens.
            $named = array_intersect_key($match, self::PATTERNS);

            foreach ($named as $key => $value) {
                if ($value !== '') {
                    $tokens[] = [
                        'type' => Token::from($key),
                        'text' => $match[0],
                    ];

                    $source = substr($source, strlen($match[0]));

                    continue 2;
                }
            }
        }

        self::$time  += microtime(true) - $start;
        self::$total += 1;

        return $tokens;
    }

}