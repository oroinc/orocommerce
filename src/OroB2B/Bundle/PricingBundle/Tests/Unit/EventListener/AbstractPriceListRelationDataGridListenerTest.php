<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\EventListener\AccountDataGridListener;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

abstract class AbstractPriceListRelationDataGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountDataGridListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListToAccountRepository
     */
    protected $repository;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->method('getManagerForClass')->willReturn($this->manager);
    }

    public function testOnResultAfter()
    {
        $relation = $this->createRelation();
        $this->repository->method('getRelationsByHolders')->willReturn([$relation]);
        $config = DatagridConfiguration::create([]);
        $parameters = new ParameterBag();

        $dataGrid = new Datagrid('test_grid', $config, $parameters);

        $eventBuildBefore = new BuildBefore($dataGrid, $config);

        $record = new ResultRecord(['name' => 'test']);
        $event = new OrmResultAfter($dataGrid, [$record]);

        $this->listener->onBuildBefore($eventBuildBefore);
        $this->listener->onResultAfter($event);
        $configArray = $config->toArray();
        $this->assertSame(
            $configArray['columns']['price_lists'],
            [
                'label' => 'orob2b.pricing.pricelist.entity_plural_label',
                'type' => 'twig',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/priceLists.html.twig',
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

    /**
     * @return BasePriceListRelation
     */
    abstract protected function createRelation();
}
