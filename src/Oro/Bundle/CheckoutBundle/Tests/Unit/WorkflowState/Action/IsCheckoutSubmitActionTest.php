<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\IsCheckoutSubmitAction;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsCheckoutSubmitActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var IsCheckoutSubmitAction */
    private $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->action = new IsCheckoutSubmitAction($this->contextAccessor, $this->requestStack);

        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($dispatcher);
    }

    public function testExecuteForPostMethod()
    {
        $attribute = new PropertyPath('attribute');
        $options = [
            'attribute' => $attribute,
        ];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with([], $attribute, true);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testExecuteForGetMethod()
    {
        $attribute = new PropertyPath('attribute');
        $options = [
            'attribute' => $attribute,
        ];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with([], $attribute, false);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter "attribute" is required
     */
    public function testInitializeWithoutAttributeField()
    {
        $options = [];

        $this->action->initialize($options);
    }
}
