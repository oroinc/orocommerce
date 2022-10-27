<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Compiler;

use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\ProductBundle\Expression\QueryConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Component\Expression\QueryExpressionBuilder;

class ProductAssignmentRuleCompilerTest extends \PHPUnit\Framework\TestCase
{
    private ProductAssignmentRuleCompiler $compiler;

    protected function setUp(): void
    {
        $expressionParser = $this->createMock(ExpressionParser::class);
        $expressionPreprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $nodeConverter = $this->createMock(NodeToQueryDesignerConverter::class);
        $queryConverter = $this->createMock(QueryConverter::class);
        $expressionBuilder = $this->createMock(QueryExpressionBuilder::class);
        $cache = $this->createMock(RuleCache::class);

        $this->compiler = new ProductAssignmentRuleCompiler(
            $expressionParser,
            $expressionPreprocessor,
            $nodeConverter,
            $queryConverter,
            $expressionBuilder,
            $cache
        );
    }

    public function testCompileThrowsExceptionWhenPriceListHasNoId(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                sprintf('Cannot compile product assignment rule: %s was expected to have id', PriceList::class)
            )
        );

        $this->compiler->compile(new PriceList(), []);
    }
}
