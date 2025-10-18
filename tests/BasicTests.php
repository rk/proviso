<?php

namespace Tests\Unit;

use RK\Proviso\Factory;
use RK\Proviso\Proxy;
use RK\Proviso\Token;
use RK\Proviso\Tokenizer;
use PHPUnit\Framework\TestCase;

class BasicTests extends TestCase
{

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTokenize()
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize("Y_Foo NOT IN ('A', 'B', 'C')");

        $this->assertCount(13, $tokens, 'Invalid number of tokens returned');
        $this->assertEquals(Token::Variable, $tokens[0]['type']);
        $this->assertEquals(Token::Whitespace, $tokens[1]['type']);
        $this->assertEquals(Token::Operator, $tokens[2]['type']);
        $this->assertEquals(Token::Whitespace, $tokens[3]['type']);
        $this->assertEquals(Token::Bounds, $tokens[4]['type']);
        $this->assertEquals(Token::String, $tokens[5]['type']);
        $this->assertEquals(Token::Bounds, $tokens[6]['type']);
        $this->assertEquals(Token::Whitespace, $tokens[7]['type']);
        $this->assertEquals(Token::String, $tokens[8]['type']);
        $this->assertEquals(Token::Bounds, $tokens[9]['type']);
        $this->assertEquals(Token::Whitespace, $tokens[10]['type']);
        $this->assertEquals(Token::String, $tokens[11]['type']);
        $this->assertEquals(Token::Bounds, $tokens[12]['type']);
    }

    public function testComplexTokenize()
    {
        $tokenizer = Factory::makeTokenizer();
        $tokens = $tokenizer->tokenize("Z_UnitMountedHeatCapacity IN ('1', '2', '3', '4') OR (Z_UnitMountedHeatCapacity IN ('5', '6') AND Z_MainPowerSplitEvapPackaged IN ('4', '5'))");

        $this->assertCount(44, $tokens);

        $expected = [
            Token::Variable,
            Token::Whitespace,
            Token::Operator,
            Token::Whitespace,
            Token::Bounds,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::Conjunction,
            Token::Whitespace,
            Token::Bounds,
            Token::Variable,
            Token::Whitespace,
            Token::Operator,
            Token::Whitespace,
            Token::Bounds,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::Conjunction,
            Token::Whitespace,
            Token::Variable,
            Token::Whitespace,
            Token::Operator,
            Token::Whitespace,
            Token::Bounds,
            Token::String,
            Token::Bounds,
            Token::Whitespace,
            Token::String,
            Token::Bounds,
            Token::Bounds,
        ];

        $this->assertEquals($expected, array_column($tokens, 'type'));
    }

    public function testBasicParser()
    {
        $context   = Factory::makeContext();
        $tokenizer = Factory::makeTokenizer();
        $parser    = Factory::makeParser($tokenizer);
        $clause    = $parser->parse("Z_Cabinet NOT IN ('E', 'F')");

        // Test: clause invalid
        $context->set('Z_Cabinet', 'E');
        $this->assertFalse($clause->result($context)->value());

        // Test: clause valid
        $context->set('Z_Cabinet', 'C');
        $this->assertTrue($clause->result($context)->value());
    }

    public function testMixedConjunctions()
    {
        $context   = Factory::makeContext();
        $tokenizer = Factory::makeTokenizer();
        $parser    = Factory::makeParser($tokenizer);
        $clause    = $parser->parse("Z_CoolingConfiguration IN ('C', 'G', 'W') OR Z_CoolingConfiguration IN ('H') AND Z_Condenser IN ('T', 'U')");

        // Test: all clauses invalid
        $context->set('Z_CoolingConfiguration', '0');
        $context->set('Z_Condenser', '0');
        $this->assertFalse($clause->result($context)->value());

        // Implicit grouping is from LTR, so the clauses are resolved in this
        // order: (A | B) & C

        // Test: first group valid
        $context->set('Z_CoolingConfiguration', 'C');
        $this->assertFalse($clause->result($context)->value());

        // Test: second group valid
        $context->set('Z_Condenser', 'T');
        $this->assertTrue($clause->result($context)->value());
    }

    public function testGroupingParser()
    {
        $context   = Factory::makeContext();
        $tokenizer = Factory::makeTokenizer();
        $parser    = Factory::makeParser($tokenizer);
        $clause    = $parser->parse("Z_UnitMountedHeatCapacity IN ('1', '2', '3', '4') OR (Z_UnitMountedHeatCapacity IN ('5', '6') AND Z_MainPowerSplitEvapPackaged IN ('4', '5'))");

        // Test: all clauses invalid
        $context->set('Z_UnitMountedHeatCapacity', '0');
        $context->set('Z_MainPowerSplitEvapPackaged', '0');
        $this->assertFalse($clause->result($context)->value());

        // Test: first clause valid
        $context->set('Z_UnitMountedHeatCapacity', '1');
        $this->assertTrue($clause->result($context)->value());

        // Test: invalid without second clause fully valid
        $context->set('Z_UnitMountedHeatCapacity', '5');
        $this->assertFalse($clause->result($context)->value());

        // Test: second clause valid
        $context->set('Z_MainPowerSplitEvapPackaged', '4');
        $this->assertTrue($clause->result($context)->value());
    }

    public function testComplexParse()
    {
        $context   = Factory::makeContext();
        $tokenizer = Factory::makeTokenizer();
        $parser    = Factory::makeParser($tokenizer);
        $clause    = $parser->parse("(Z_RemoteCondenserCabinet IN ('0') AND Z_CoolingConfiguration IN ('C', 'G', 'W')) OR (Z_RemoteCondenserCabinet IN ('2') AND Z_Condenser IN ('T', 'U'))");

        // Test: all clauses invalid
        $context->set('Z_RemoteCondenserCabinet', '0');
        $context->set('Z_CoolingConfiguration', '0');
        $context->set('Z_Condenser', '0');
        $this->assertFalse($clause->result($context)->value());

        // Test: first clause valid
        $context->set('Z_RemoteCondenserCabinet', '0');
        $context->set('Z_CoolingConfiguration', 'C');
        $this->assertTrue($clause->result($context)->value());

        // Test: invalid without second clause fully valid
        $context->set('Z_RemoteCondenserCabinet', '2');
        $context->set('Z_CoolingConfiguration', '0');
        $this->assertFalse($clause->result($context)->value());

        // Test: second clause valid
        $context->set('Z_Condenser', 'T');
        $this->assertTrue($clause->result($context)->value());
    }

    public function testProxy()
    {
        Proxy::set('Z_Foo', '1');

        $this->assertTrue(Proxy::execute('Z_Foo IN ("1")'));
    }

    public function testVeryComplexExpression()
    {
        $context = Factory::makeContext();
        $context->fill([
            'Z_ModelFamily' => '0',
            'Z_Cabinet' => '0',
            'Z_CoolingConfiguration' => '0',
            'Z_MainPowerSplitEvapPackaged' => '0',
        ]);

        $expression = Proxy::compile("Z_ModelFamily IN ('HK1') AND Z_Cabinet IN ('A', 'Z') AND ((Z_CoolingConfiguration IN ('A','H','E','G','W') AND Z_MainPowerSplitEvapPackaged IN ('1','5','7')) OR Z_CoolingConfiguration IN ('C'))");
        $expression->setContext($context);

        $this->assertFalse($expression());
    }
}
