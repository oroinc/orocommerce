<?php

namespace Oro\Bundle\PricingBundle\ORM\Query\AST\Functions\String;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Generates SQL for the UUID_GENERATE_V4() database function in Doctrine queries.
 */
class UUID extends FunctionNode
{
    #[\Override]
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     *
     * @return string
     */
    #[\Override]
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'UUID_GENERATE_V4()';
    }
}
