<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;

/**
 * @dbIsolation
 */
class ProductVisibilityRestrictionListenerTest extends AbstractSearchWebTestCase
{
    /**
     * @var EngineV2Interface
     */
    private $engine;

    /**
     * @var \Closure
     */
    private $listener;

    /**
     * @var string
     */
    private static $testValue;

    public static function setUpBeforeClass()
    {
        self::markTestSkipped('BB-4191');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->engine = $this->client->getContainer()->get('oro_website_search.engine');

        self::$testValue = 'test_'.uniqid();

        $this->listener = function (ProductSearchQueryRestrictionEvent $event) {
            $expr = Criteria::expr();

            $event->getQuery()->getCriteria()->andWhere($expr->eq('name', self::$testValue));
        };


        $this->dispatcher->addListener(ProductSearchQueryRestrictionEvent::NAME, $this->listener);
    }

    protected function tearDown()
    {
        $this->dispatcher->removeListener(ProductSearchQueryRestrictionEvent::NAME, $this->listener);

        parent::tearDown();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testRestrictsVisibilityForJustProducts()
    {
        $query = new Query();

        $query->from([Product::class]);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            foreach ($where->getExpressionList() as $expr) {
                if (($expr->getField() === 'sku') && ($expr->getValue()->getValue() === null)) {
                    $foundSkuExpression = true;
                }

                if (($expr->getField() === 'name') && ($expr->getValue()->getValue() === self::$testValue)) {
                    $foundCustomExpression = true;
                }
            }
        }

        if ($where instanceof Comparison) {
            if (($where->getField() === 'name') && ($where->getValue()->getValue() === self::$testValue)) {
                $foundCustomExpression = true;
            }
        }

        $this->assertFalse($foundSkuExpression, 'Sku is null expression should not be applied.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    public function testRestrictsVisibilityForManyEntities()
    {
        $query = new Query();

        $query->from([Product::class, 'foo']);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            foreach ($where->getExpressionList() as $expr) {
                if (($expr->getField() === 'sku') && ($expr->getValue()->getValue() === null)) {
                    $foundSkuExpression = true;
                }

                if (($expr->getField() === 'name') && ($expr->getValue()->getValue() === self::$testValue)) {
                    $foundCustomExpression = true;
                }
            }
        }

        $this->assertTrue($foundSkuExpression, 'Sku is null expression not found.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    public function testRestrictsVisibilityForManyEntitiesWithPreviouslyPopulatedWhere()
    {
        $query = new Query();

        $query->from([Product::class, 'foo']);
        $query->getCriteria()->andWhere(Criteria::expr()->eq('sku', 'bar'));

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            foreach ($where->getExpressionList() as $mainExpr) {
                if ($mainExpr instanceof CompositeExpression) {
                    foreach ($mainExpr->getExpressionList() as $expr) {
                        if (($expr->getField() === 'sku') && ($expr->getValue()->getValue() === null)) {
                            $foundSkuExpression = true;
                        }

                        if (($expr->getField() === 'name') && ($expr->getValue()->getValue() === self::$testValue)) {
                            $foundCustomExpression = true;
                        }
                    }
                }
            }
        }

        $this->assertTrue($foundSkuExpression, 'Sku is null expression not found.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }
}
