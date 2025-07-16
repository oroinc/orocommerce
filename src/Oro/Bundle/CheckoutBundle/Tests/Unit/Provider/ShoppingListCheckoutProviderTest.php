<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\ShoppingListCheckoutProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\CurrentUserProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListCheckoutProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $managerRegistry;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CurrentUserProvider|MockObject $currentUserProvider;
    private WorkflowManager|MockObject $workflowManager;
    private ShoppingListCheckoutProvider $currentCheckoutProvider;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->currentCheckoutProvider = new ShoppingListCheckoutProvider(
            $this->managerRegistry,
            $this->userCurrencyManager,
            $this->currentUserProvider,
            $this->workflowManager,
        );
    }

    public function testGetCurrentCheckoutWithoutUser(): void
    {
        $this->workflowManager
            ->expects($this->never())
            ->method('getAvailableWorkflowByRecordGroup');

        $shoppingList = new ShoppingList();
        $this->assertNull($this->currentCheckoutProvider->getCheckout($shoppingList));
    }

    public function testGetCurrentCheckoutWithoutAvailableWorkflow(): void
    {
        $customerUser = new CustomerUser();
        $this->currentUserProvider
            ->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($customerUser);

        $this->workflowManager
            ->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->willReturn(null);

        $this->userCurrencyManager
            ->expects($this->never())
            ->method('getUserCurrency');

        $shoppingList = new ShoppingList();
        $this->assertNull($this->currentCheckoutProvider->getCheckout($shoppingList));
    }

    public function testGetCurrentCheckout(): void
    {
        $customerUser = new CustomerUser();
        $this->currentUserProvider
            ->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($customerUser);

        $workflow = $this->createMock(Workflow::class);
        $workflow
            ->expects($this->once())
            ->method('getName')
            ->willReturn('b2b_flow_checkout');
        $this->workflowManager
            ->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->willReturn($workflow);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $checkout = new Checkout();
        $shoppingList = new ShoppingList();
        $checkoutRepository = $this->createMock(CheckoutRepository::class);
        $checkoutRepository
            ->expects($this->once())
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->with($customerUser, ['shoppingList' => $shoppingList], 'b2b_flow_checkout', $currency)
            ->willReturn($checkout);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($checkoutRepository);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->assertEquals($checkout, $this->currentCheckoutProvider->getCheckout($shoppingList));
    }
}
