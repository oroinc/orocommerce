<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Handler\RequestHandler;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class RequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TotalProcessorProvider */
    protected $totalProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestHandler */
    protected $requestHandler;

    protected function setUp()
    {
        $this->totalProvider =
            $this->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
                ->disableOriginalConstructor()->getMock();

        $this->eventDispatcher =
            $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                ->disableOriginalConstructor()->getMock();

        $this->securityFacade =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
                ->disableOriginalConstructor()->getMock();

        $this->requestStack =
            $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
                ->disableOriginalConstructor()->getMock();

        $this->entityRoutingHelper =
            $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
                ->disableOriginalConstructor()->getMock();

        $this->doctrine =
            $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\Registry')
                ->disableOriginalConstructor()->getMock();

        $this->requestHandler = new RequestHandler(
            $this->totalProvider,
            $this->eventDispatcher,
            $this->securityFacade,
            $this->requestStack,
            $this->entityRoutingHelper,
            $this->doctrine
        );
    }

    /**
     * @dataProvider getTotalsProvider
     * @expectedException Oro\Bundle\EntityBundle\Exception\EntityNotFoundException
     *
     * @param string $entityClassName
     * @param int $entityId
     */
    public function testGetExistEntity($entityClassName, $entityId)
    {
        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);

        if ($entityClassName && $entityId === null) {
            $repository = $this->initRepository(null);
            $manager = $this->initManager($repository);

            $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);
        }

        $this->requestHandler->getTotals($entityClassName, $entityId);
    }

    /**
     * @return array
     */
    public function getTotalsProvider()
    {
        return [
            'empty Class Name' => [
                'entityClassName' => '',
                'entityId' => null
            ],
            'No empty Class Name and EntityId' => [
                'entityClassName' => 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub',
                'entityId' => null
            ]
        ];
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testGetTotalsNoAccessView()
    {
        $entityClassName = 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub';
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);

        $repository = $this->initRepository(new $entityClassName());
        $manager = $this->initManager($repository);

        $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);

        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(false);

        $this->requestHandler->getTotals($entityClassName, $entityId);
    }

    public function testGetTotals()
    {
        $entityClassName = 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub';
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);

        $repository = $this->initRepository(new $entityClassName());
        $manager = $this->initManager($repository);

        $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);
        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(true);

        $this->prepareTotal();
        $totals = $this->requestHandler->getTotals($entityClassName, $entityId);
        $expectedTotals = $this->getExpectedTotal();

        self::assertEquals($expectedTotals, $totals);
    }

    /**
     * @dataProvider getRecalculateTotalProvider
     *
     * @param $entityClassName
     * @param $entityId
     */
    public function testRecalculateTotalsWithoutEntityID($entityClassName, $entityId)
    {
        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);

        if ($entityId > 0) {
            $entity = new $entityClassName();

            $repository = $this->initRepository($entity);
            $manager = $this->initManager($repository);

            $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);
            $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(true);
        } else {
            $entity = new $entityClassName();
        }

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturn($event);

        $this->prepareTotal();
        $totals = $this->requestHandler->recalculateTotals($entityClassName, $entityId);
        $expectedTotals = $this->getExpectedTotal();

        self::assertEquals($expectedTotals, $totals);
    }

    /**
     * @return array
     */
    public function getRecalculateTotalProvider()
    {
        return [
            'test with entityId = 0' => [
                'entityClassName' => 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub',
                'entityId' => 0
            ],
            'test with entityId > 0' => [
                'entityClassName' => 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub',
                'entityId' => 1
            ]
        ];
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testRecalculateTotalsNoAccessEdit()
    {
        $entityClassName = 'OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub';
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);
        $entity = new $entityClassName();

        $repository = $this->initRepository($entity);
        $manager = $this->initManager($repository);

        $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);
        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(false);

        $this->requestHandler->recalculateTotals($entityClassName, $entityId);
    }

    protected function prepareTotal()
    {
        $total = new Subtotal();
        $total->setType('Total');
        $total->setAmount(100);
        $total->setCurrency('USD');
        $total->setLabel('Total');
        $total->setVisible(true);

        $subtotals = new ArrayCollection();

        $subtotal = new Subtotal();
        $subtotal->setType('Shipping Cost');
        $subtotal->setAmount(100);
        $subtotal->setCurrency('USD');
        $subtotal->setLabel('Shipping Cost');
        $subtotal->setVisible(true);

        $subtotals->add($subtotal);

        $this->totalProvider->expects($this->once())->method('getTotal')->willReturn($total);
        $this->totalProvider->expects($this->once())->method('getSubtotals')->willReturn($subtotals);

    }


    protected function getExpectedTotal()
    {
        return [
            'total' => [
                'type' => 'Total',
                'label' => 'Total',
                'amount' => 100,
                'currency' => 'USD',
                'visible' => true
            ],
            'subtotals' => [
                [
                    'type' => 'Shipping Cost',
                    'label' => 'Shipping Cost',
                    'amount' => 100,
                    'currency' => 'USD',
                    'visible' => true
                ]

            ]
        ];
    }

    protected function initRepository($returnEntity)
    {
        $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('find')->willReturn($returnEntity);

        return $repository;
    }

    protected function initManager($repository)
    {
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
