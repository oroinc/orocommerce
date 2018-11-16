<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CustomerBundle\Handler\CustomerRegistrationHandler;
use Oro\Bundle\CustomerBundle\Handler\ForgotPasswordHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class CheckoutWorkflowHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var  CheckoutWorkflowHelper */
    private $helper;

    /** @var  WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $transitionProvider = $this->createMock(TransitionProvider::class);
        $transitionFormProvider = $this->createMock(TransitionFormProvider::class);
        $errorHandler = $this->createMock(CheckoutErrorHandler::class);
        $lineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $registrationHandler = $this->createMock(CustomerRegistrationHandler::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $forgotPasswordHandler = $this->createMock(ForgotPasswordHandler::class);

        $this->helper = new CheckoutWorkflowHelper(
            $this->workflowManager,
            $actionGroupRegistry,
            $transitionProvider,
            $transitionFormProvider,
            $errorHandler,
            $lineItemsManager,
            $registrationHandler,
            $forgotPasswordHandler,
            $eventDispatcher,
            $translator
        );
    }

    /**
     * @dataProvider workflowItemProvider
     *
     * @param $items
     * @param $expectException
     * @param $expected
     */
    public function testGetWorkflowItem($items, $expectException, $expected)
    {
        $checkout = $this->createMock(Checkout::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        if ($expectException) {
            $this->expectException(NotFoundHttpException::class);
            $this->expectExceptionMessage('Unable to find correct WorkflowItem for current checkout');
        }
        $result = $this->helper->getWorkflowItem($checkout);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return \Generator
     */
    public function workflowItemProvider()
    {
        yield 'Items count equals one' => [
            'items' => [
                $this->getEntity(WorkflowItem::class, ['id' => 1])
            ],
            'expectException' => false,
            'expectedResult' => $this->getEntity(WorkflowItem::class, ['id' => 1])
        ];

        yield 'Items count more than one' => [
            'items' => [
                $this->getEntity(WorkflowItem::class, ['id' => 1]),
                $this->getEntity(WorkflowItem::class, ['id' => 2]),
            ],
            'expectException' => true,
            'expectedResult' => $this->getEntity(WorkflowItem::class, ['id' => 1])
        ];
    }
}
