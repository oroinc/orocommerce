<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Visibility\Restrictions;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions\RestrictProductVariationsEventListener;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\HttpFoundation\ParameterBag;

class RestrictProductVariationsEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager | \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FrontendHelper | \PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var QueryBuilderModifierInterface | \PHPUnit\Framework\MockObject\MockObject */
    private $dbQueryBuilderModifier;

    /** @var RestrictProductVariationsEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->dbQueryBuilderModifier = $this->createMock(QueryBuilderModifierInterface::class);

        $this->listener = new RestrictProductVariationsEventListener(
            $this->configManager,
            $this->frontendHelper,
            $this->dbQueryBuilderModifier
        );
    }

    /**
     * @dataProvider dataProviderSearch
     */
    public function testOnSearchQuery(
        string $configValue,
        bool $isFrontendRequest,
        bool $isRestrictionApplicable,
        Query $query
    ) {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_product.display_simple_variations', false, false, null, $configValue]
            ]);

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);
        $event = new ProductSearchQueryRestrictionEvent($query);
        $this->listener->onSearchQuery($event);

        $whereExpression = $event->getQuery()->getCriteria()->getWhereExpression();
        if ($isRestrictionApplicable) {
            $expectedWhereExpr = Criteria::expr()->eq('integer.is_variant', 0);
            $this->assertEquals($expectedWhereExpr, $whereExpression);
        } else {
            $this->assertEmpty(
                $whereExpression,
                'No expression must be applicable !'
            );
        }
    }

    public function testOnSearchQueryWithVariantCriteriaExist()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturnMap([
                [
                    'oro_product.display_simple_variations',
                    false,
                    false,
                    null,
                    Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY
                ]
            ]);

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $expression = Criteria::expr()->eq('integer.is_variant', 1);
        $criteria = Criteria::create();
        $criteria->andWhere($expression);

        $query = new Query();
        $query->addSelect('integer.system_entity_id as product_id');
        $query->setCriteria($criteria);

        $event = new ProductSearchQueryRestrictionEvent($query);
        $this->listener->onSearchQuery($event);

        $this->assertEquals(
            $expression,
            $event->getQuery()->getCriteria()->getWhereExpression()
        );
    }

    /**
     * @dataProvider dataProviderDb
     */
    public function testOnDBQuery(string $configValue, bool $isFrontendRequest, bool $isRestrictionApplicable)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.display_simple_variations')
            ->willReturn($configValue);

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        $qb = $this->createMock(QueryBuilder::class);

        if ($isRestrictionApplicable) {
            $this->dbQueryBuilderModifier->expects($this->once())
                ->method('modify')
                ->with($qb);
        } else {
            $this->dbQueryBuilderModifier->expects($this->never())
                ->method('modify');
        }

        $event = new ProductDBQueryRestrictionEvent($qb, new ParameterBag());
        $this->listener->onDBQuery($event);
    }

    public function dataProviderSearch(): array
    {
        return [
            'Restriction applicable not autocomplete' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => true,
                'query' => (new Query())->addSelect('integer.system_entity_id as product_id')
            ],
            'Restriction applicable autocomplete' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => true,
                'query' => (new Query())->addSelect('integer.system_entity_id as autocomplete_record_id')
            ],
            'Restriction applicable not autocomplete hide catalog' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => true,
                'query' => (new Query())->addSelect('integer.system_entity_id as product_id')
            ],
            'Restriction applicable autocomplete hide catalog' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => false,
                'query' => (new Query())->addSelect('integer.system_entity_id as autocomplete_record_id')
            ],
            'Restriction applicable autocomplete hide catalog not frontend' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false,
                'query' => (new Query())->addSelect('integer.system_entity_id as autocomplete_record_id')
            ],
            'Is not frontend request' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false,
                'query' => (new Query())->addSelect('integer.system_entity_id as product_id')
            ],
            'Config value is display everywhere' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => false,
                'query' => (new Query())->addSelect('integer.system_entity_id as product_id')
            ],
            'Config value is display everywhere and is not frontend' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false,
                'query' => (new Query())->addSelect('integer.system_entity_id as product_id')
            ],
        ];
    }

    public function dataProviderDb(): array
    {
        return [
            'Restriction applicable' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => true
            ],
            'Is not frontend request' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false
            ],
            'Config value is display everywhere' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => true,
                'isRestrictionApplicable' => false
            ],
            'Config value is display everywhere and is not frontend' => [
                'configValue' => Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE,
                'isFrontendRequest' => false,
                'isRestrictionApplicable' => false
            ],
        ];
    }
}
