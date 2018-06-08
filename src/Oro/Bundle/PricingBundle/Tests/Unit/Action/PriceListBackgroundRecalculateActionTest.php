<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Action\PriceListBackgroundRecalculateAction;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class PriceListBackgroundRecalculateActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var PriceListProductAssignmentBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $assignmentBuilder;

    /** @var ProductPriceBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $productPriceBuilder;

    /** @var DependentPriceListProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $dependentPriceListProvider;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PriceListBackgroundRecalculateAction */
    protected $action;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);
        $this->productPriceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->dependentPriceListProvider = $this->createMock(DependentPriceListProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new PriceListBackgroundRecalculateAction(
            $this->contextAccessor,
            $this->assignmentBuilder,
            $this->productPriceBuilder,
            $this->dependentPriceListProvider,
            $this->doctrineHelper
        );
        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testInitializeFail()
    {
        $this->setExpectedException(InvalidParameterException::class);
        $this->action->initialize([
            PriceListBackgroundRecalculateAction::OPTION_KEY_PRICE_LIST => null
        ]);
    }

    public function testExecuteActionFail()
    {
        $priceListOption = $this->createMock(PropertyPathInterface::class);
        $this->action->initialize([
            PriceListBackgroundRecalculateAction::OPTION_KEY_PRICE_LIST => $priceListOption
        ]);

        $context = $this->createMock(ActionData::class);
        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, $priceListOption)
            ->willReturn(new \stdClass());

        $this->setExpectedException(InvalidParameterException::class);
        $this->action->execute($context);
    }

    public function testExecuteAction()
    {
        $priceList = new PriceList();
        $priceList->setActual(false);

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($priceList);

        $dependentPriceList1 = new PriceList();
        $dependentPriceList1->setActual(false);
        $dependentPriceList2 = new PriceList();
        $dependentPriceList2->setActual(false);

        $this->dependentPriceListProvider->expects($this->once())
            ->method('appendDependent')
            ->with([$priceList])
            ->willReturn([$priceList, $dependentPriceList1, $dependentPriceList2]);

        $this->assignmentBuilder->expects($this->exactly(3))
            ->method('buildByPriceListWithoutEventDispatch')
            ->withConsecutive([$priceList], [$dependentPriceList1], [$dependentPriceList2]);
        $this->productPriceBuilder->expects($this->exactly(3))
            ->method('buildByPriceListWithoutTriggerSend')
            ->withConsecutive([$priceList], [$dependentPriceList1], [$dependentPriceList2]);

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())->method('flush')->with([$priceList, $dependentPriceList1, $dependentPriceList2]);
        $this->productPriceBuilder->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->atLeastOnce())->method('dispatch');

        $this->action->execute($this->createMock(ActionData::class));
        $this->assertTrue($priceList->isActual());
        $this->assertTrue($dependentPriceList1->isActual());
        $this->assertTrue($dependentPriceList2->isActual());
    }
}
