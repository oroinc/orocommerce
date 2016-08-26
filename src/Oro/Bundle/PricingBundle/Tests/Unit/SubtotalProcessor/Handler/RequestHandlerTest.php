<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Handler\RequestHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class RequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TotalProcessorProvider */
    protected $totalProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestHandler */
    protected $requestHandler;

    protected function setUp()
    {
        $this->totalProvider =
            $this->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
                ->disableOriginalConstructor()->getMock();

        $this->eventDispatcher =
            $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                ->disableOriginalConstructor()->getMock();

        $this->securityFacade =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
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
            $this->entityRoutingHelper,
            $this->doctrine
        );
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

        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturn($event);

        $this->prepareTotal();

        $this->totalProvider
            ->expects($this->once())
            ->method('enableRecalculation');

        $totals = $this->requestHandler->recalculateTotals($entityClassName, $entityId, $request);
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
                'entityClassName' => 'Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub',
                'entityId' => 0
            ],
            'test with entityId > 0' => [
                'entityClassName' => 'Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub',
                'entityId' => 1
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testRecalculateTotalsNoAccessView()
    {
        $entityClassName = 'Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub';
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())->method('resolveEntityClass')->willReturn($entityClassName);
        $entity = new $entityClassName();

        $repository = $this->initRepository($entity);
        $manager = $this->initManager($repository);

        $this->doctrine->expects($this->once())->method('getManager')->willReturn($manager);
        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(false);

        $this->requestHandler->recalculateTotals($entityClassName, $entityId);
    }

    /**
     * Init totalProvider
     */
    protected function prepareTotal()
    {
        $this->totalProvider->expects($this->once())->method('enableRecalculation')->willReturn($this->totalProvider);
        $this->totalProvider->expects($this->once())->method('getTotalWithSubtotalsAsArray')
            ->willReturn($this->getExpectedTotal());
    }

    /**
     * @return array
     */
    protected function getExpectedTotal()
    {
        return [
            'total' => [
                'type' => 'Total',
                'label' => 'Total',
                'amount' => 100,
                'currency' => 'USD',
                'visible' => true,
                'data' => null
            ],
            'subtotals' => [
                [
                    'type' => 'Shipping Cost',
                    'label' => 'Shipping Cost',
                    'amount' => 100,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => null
                ]

            ]
        ];
    }

    /**
     * @param $returnEntity
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function initRepository($returnEntity)
    {
        $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('find')->willReturn($returnEntity);

        return $repository;
    }

    /**
     * @param $repository
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function initManager($repository)
    {
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
