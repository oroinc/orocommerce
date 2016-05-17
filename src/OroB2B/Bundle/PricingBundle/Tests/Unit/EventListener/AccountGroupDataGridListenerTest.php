<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\PricingBundle\EventListener\AccountDataGridListener;

use Symfony\Component\Translation\TranslatorInterface;

class AccountGroupDataGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
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

    public function setUp()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
        $repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceListToAccountGroup', $repository]
        ]);

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->method('getManagerForClass')->willReturn($this->manager);
        /** @var TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->listener = new AccountDataGridListener($this->registry, $translator);
    }

    public function testOnResultAfter()
    {
        /** @var DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($dataGrid);
        $this->listener->onResultAfter($event);
    }
}
