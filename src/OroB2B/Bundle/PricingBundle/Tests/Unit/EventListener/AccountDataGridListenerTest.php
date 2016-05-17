<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\EventListener\AccountDataGridListener;

use Symfony\Component\Translation\TranslatorInterface;

class AccountDataGridListenerTest extends \PHPUnit_Framework_TestCase
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
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceListToAccount', $this->repository]
        ]);

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->method('getManagerForClass')->willReturn($this->manager);
        /** @var TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->listener = new AccountDataGridListener($this->registry, $translator);
    }

    public function testOnResultAfter()
    {
        $relation = new PriceListToAccount();
        $account = $this->getMock(Account::class);
        $priceList = $this->getMock(PriceList::class);
        $relation->setAccount($account);
        $relation->setPriceList($priceList);
        $this->repository->method('getRelationsByHolders')->willReturn([]);
        $config = DatagridConfiguration::create([]);
        $parameters = new ParameterBag();

        $dataGrid = new Datagrid('test_grid', $config, $parameters);

        $eventBuildBefore = new BuildBefore($dataGrid, $config);

        $record = new ResultRecord(['name' => 'test']);
        $event = new OrmResultAfter($dataGrid, [$record]);

        $this->listener->onBuildBefore($eventBuildBefore);
        $this->listener->onResultAfter($event);
    }
}
