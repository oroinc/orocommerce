<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Compiler;

use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\ProductBundle\Expression\QueryConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Component\Expression\QueryExpressionBuilder;

class PriceListRuleCompilerTest extends \PHPUnit\Framework\TestCase
{
    private PriceListRuleCompiler $compiler;

    protected function setUp(): void
    {
        $expressionParser = $this->createMock(ExpressionParser::class);
        $expressionPreprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $nodeConverter = $this->createMock(NodeToQueryDesignerConverter::class);
        $queryConverter = $this->createMock(QueryConverter::class);
        $expressionBuilder = $this->createMock(QueryExpressionBuilder::class);
        $cache = $this->createMock(RuleCache::class);

        $this->compiler = new PriceListRuleCompiler(
            $expressionParser,
            $expressionPreprocessor,
            $nodeConverter,
            $queryConverter,
            $expressionBuilder,
            $cache
        );
    }

    public function testCompileThrowsExceptionWhenPriceRuleHasNoId(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                sprintf('Cannot compile price list rule: %s was expected to have id', PriceRule::class)
            )
        );

        $this->compiler->compile(new PriceRule(), []);
    }
}
