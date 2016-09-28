<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * @dbIsolation
 */
class ProductVisibilityRestrictionListenerTest extends WebTestCase
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private static $testValue;

    /**
     * @var bool
     */
    private static $listenerInitialized = false;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->engine = $this->client->getContainer()->get('oro_website_search.engine');
        $this->eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        self::$testValue = 'test_'.uniqid();

        if (!self::$listenerInitialized) {
            $this->eventDispatcher->addListener(
                ProductSearchQueryRestrictionEvent::NAME,
                function (ProductSearchQueryRestrictionEvent $event) {
                    $expr = Criteria::expr();

                    $event->getQuery()->getCriteria()->andWhere($expr->eq('name', self::$testValue));
                }
            );
        }

        self::$listenerInitialized = true;
    }

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
