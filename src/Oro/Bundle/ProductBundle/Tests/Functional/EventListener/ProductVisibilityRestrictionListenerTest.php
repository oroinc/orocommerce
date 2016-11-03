<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductVisibilityRestrictionListenerTest extends WebTestCase
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

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->engine = $this->getContainer()->get('oro_website_search.engine');

        static::$testValue = 'test_' . uniqid();

        $this->listener = function (ProductSearchQueryRestrictionEvent $event) {
            $expr = Criteria::expr();

            $event->getQuery()->getCriteria()->andWhere($expr->eq('name', self::$testValue));
        };

        $this->dispatcher->addListener(ProductSearchQueryRestrictionEvent::NAME, $this->listener);
    }

    protected function tearDown()
    {
        $this->dispatcher->removeListener(ProductSearchQueryRestrictionEvent::NAME, $this->listener);
    }

    public function testRestrictsVisibilityForJustProducts()
    {
        $query = new Query();

        $query->from([Product::class]);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($where);
        }

        if ($where instanceof Comparison) {
            if (($where->getField() === 'name') && ($where->getValue()->getValue() === self::$testValue)) {
                $foundCustomExpression = true;
            }
        }

        $this->assertFalse($foundSkuExpression, 'Sku is null expression should not be applied.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    public function testRestrictsVisibilityForJustProductsWithProductAlias()
    {
        $productAlias = $this->getContainer()->get('oro_website_search.provider.search_mapping')
            ->getEntityAlias(Product::class);

        $query = new Query();

        $query->from([$productAlias]);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($where);
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

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($where);
        }

        $this->assertTrue($foundSkuExpression, 'Sku is null expression not found.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    public function testRestrictsVisibilityForManyEntitiesWithoutProduct()
    {
        $query = new Query();

        $query->from(['foo', 'foo2']);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($where);
        }

        $this->assertFalse($foundSkuExpression, 'Sku is null expression should not be applied.');
        $this->assertFalse($foundCustomExpression, 'Custom expression from listener should not be applied.');
    }

    public function testRestrictsVisibilityForManyEntitiesWithPreviouslyPopulatedWhere()
    {
        $query = new Query();

        $query->from([Product::class, 'foo']);
        $query->getCriteria()->andWhere(Criteria::expr()->eq('sku', 'bar'));

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            foreach ($where->getExpressionList() as $mainExpr) {
                if ($mainExpr instanceof CompositeExpression) {
                    list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($mainExpr);
                }
            }
        }

        $this->assertTrue($foundSkuExpression, 'Sku is null expression not found.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    public function testRestrictsVisibilityForAllEntities()
    {
        $query = new Query();

        $query->from(['*']);

        $this->engine->search($query);

        $where = $query->getCriteria()->getWhereExpression();

        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        if ($where instanceof CompositeExpression) {
            list($foundSkuExpression, $foundCustomExpression) = $this->checkCompositeExpression($where);
        }

        $this->assertTrue($foundSkuExpression, 'Sku is null expression not found.');
        $this->assertTrue($foundCustomExpression, 'Custom expression from listener not found.');
    }

    /**
     * @param CompositeExpression $where
     * @return array
     */
    protected function checkCompositeExpression(CompositeExpression $where)
    {
        $foundSkuExpression    = false;
        $foundCustomExpression = false;

        /** @var Comparison $expr */
        foreach ($where->getExpressionList() as $expr) {
            if ($expr instanceof CompositeExpression) {
                list($foundSkuComposite, $foundCustomComposite) = $this->checkCompositeExpression($expr);

                $foundSkuExpression    = $foundSkuExpression || $foundSkuComposite;
                $foundCustomExpression = $foundCustomExpression || $foundCustomComposite;
                continue;
            }

            if (($expr->getField() === 'sku') && ($expr->getValue()->getValue() === null)) {
                $foundSkuExpression = true;
            }

            if (($expr->getField() === 'name') && ($expr->getValue()->getValue() === self::$testValue)) {
                $foundCustomExpression = true;
            }
        }

        return [$foundSkuExpression, $foundCustomExpression];
    }
}
