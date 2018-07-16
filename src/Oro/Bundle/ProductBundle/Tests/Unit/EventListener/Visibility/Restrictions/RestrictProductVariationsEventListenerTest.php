<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Visibility\Restrictions;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions\RestrictProductVariationsEventListener;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\HttpFoundation\ParameterBag;

class RestrictProductVariationsEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager | \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FrontendHelper | \PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var RestrictProductVariationsEventListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->listener = new RestrictProductVariationsEventListener(
            $this->configManager,
            $this->frontendHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $configValue
     * @param $isFrontendRequest
     * @param $isRestrictionApplicable
     */
    public function testOnSearchQuery($configValue, $isFrontendRequest, $isRestrictionApplicable)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_product.display_simple_variations')
            ->willReturn($configValue);

        $this->frontendHelper
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        $event = new ProductSearchQueryRestrictionEvent(new Query());
        $this->listener->onSearchQuery($event);

        $whereExpression = $event->getQuery()->getCriteria()->getWhereExpression();
        if ($isRestrictionApplicable) {
            $expectedWhereExpr = Criteria::expr()->eq('integer.is_variant', 0);
            $this->assertEquals($expectedWhereExpr, $whereExpression);
        } else {
            $this->assertEmpty(
                $whereExpression,
                "No expression must be applicable !"
            );
        }
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $configValue
     * @param $isFrontendRequest
     * @param $isRestrictionApplicable
     */
    public function testOnDBQuery($configValue, $isFrontendRequest, $isRestrictionApplicable)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_product.display_simple_variations')
            ->willReturn($configValue);

        $this->frontendHelper
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        $qb = $this->createMock(QueryBuilder::class);

        if ($isRestrictionApplicable) {
            $qb
                ->expects($this->once())
                ->method('getRootAliases')
                ->willReturn(['product']);

            $qb
                ->expects($this->once())
                ->method('andWhere')
                ->with('product.parentVariantLinks IS EMPTY');
        } else {
            $qb
                ->expects($this->never())
                ->method('getRootAliases');

            $qb
                ->expects($this->never())
                ->method('andWhere');
        }

        $event = new ProductDBQueryRestrictionEvent($qb, new ParameterBag());
        $this->listener->onDBQuery($event);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            "Restriction applicable" => [
                'configValue'       => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => true
            ],
            "Is not frontend request" => [
                'configValue'       => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false
            ],
            "Config value is display everywhere" => [
                'configValue'       => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => false
            ],
            "Config value is display everywhere and is not frontend" => [
                'configValue'       => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false
            ],
        ];
    }
}
