<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListRelationDataGridListener;

abstract class AbstractPriceListRelationDataGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractPriceListRelationDataGridListener */
    protected $listener;

    /** @var PriceListToCustomerRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    public function testOnBuilderBeforeFeatureDisabled()
    {
        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->never())
            ->method('getDatagrid');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onBuildBefore($event);
    }

    public function testOnResultAfterFeatureDisabled()
    {
        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->never())
            ->method('getRecords');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        $objectId = 123;
        $relation = $this->createRelation($objectId);
        $this->repository->expects(self::any())
            ->method('getRelationsByHolders')
            ->willReturn([$relation]);
        $config = DatagridConfiguration::create([]);
        $parameters = new ParameterBag();

        $dataGrid = new Datagrid('test_grid', $config, $parameters);

        $eventBuildBefore = new BuildBefore($dataGrid, $config);

        $record = new ResultRecord(['id' => $objectId, 'name' => 'test']);
        $event = new OrmResultAfter($dataGrid, [$record]);

        $this->featureChecker->expects($this->exactly(2))
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onBuildBefore($eventBuildBefore);
        $this->listener->onResultAfter($event);
        $configArray = $config->toArray();
        $this->assertSame(
            $configArray['columns']['price_lists'],
            [
                'label' => 'oro.pricing.pricelist.entity_plural_label',
                'type' => 'twig',
                'template' => '@OroPricing/Datagrid/Column/priceLists.html.twig',
                'frontend_type' => 'html',
                'renderable' => false,
            ]
        );
        $this->assertSame(
            $record->getValue('price_lists'),
            [
                $relation->getWebsite()->getId() => [
                    'website' => $relation->getWebsite(),
                    'priceLists' => [$relation->getPriceList()],
                ]
            ]
        );
    }

    abstract protected function createRelation(int $objectId): BasePriceListRelation;
}
