<?php

namespace Oro\Bundle\PromotionBundle\ORM\Query\AST\Functions\String;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Base64 decode.
 */
class Decode extends FunctionNode
{
    public mixed $stringPrimary;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return "DECODE(" . $sqlWalker->walkArithmeticPrimary($this->stringPrimary) . ", 'base64')";
    }
}
