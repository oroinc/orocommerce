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
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListCheckoutProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $managerRegistry;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CurrentUserProvider|MockObject $currentUserProvider;
    private ShoppingListCheckoutProvider $currentCheckoutProvider;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);

        $this->currentCheckoutProvider = new ShoppingListCheckoutProvider(
            $this->managerRegistry,
            $this->userCurrencyManager,
            $this->currentUserProvider,
        );
    }

    public function testGetCurrentCheckoutWithoutUser(): void
    {
        $this->currentUserProvider
            ->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn(null);

        $this->managerRegistry
            ->expects($this->never())
            ->method('getRepository');

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

        $workflowDefinitionRepository = $this->createMock(WorkflowDefinitionRepository::class);
        $workflowDefinitionRepository
            ->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->willReturn([]);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($workflowDefinitionRepository);

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

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition
            ->expects($this->once())
            ->method('getName')
            ->willReturn('b2b_flow_checkout');
        $workflowDefinition
            ->expects($this->once())
            ->method('getExclusiveActiveGroups')
            ->willReturn(['b2b_checkout_flow']);

        $workflowDefinitionRepository = $this->createMock(WorkflowDefinitionRepository::class);
        $workflowDefinitionRepository
            ->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->willReturn([$workflowDefinition]);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($workflowDefinitionRepository);

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
