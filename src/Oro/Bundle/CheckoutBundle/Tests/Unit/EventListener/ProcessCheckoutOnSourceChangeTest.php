<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ProcessCheckoutOnSourceChange;
use Oro\Bundle\CheckoutBundle\Provider\ShoppingListCheckoutProvider;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class ProcessCheckoutOnSourceChangeTest extends TestCase
{
    private ProcessCheckoutOnSourceChange $listener;
    private ShoppingListCheckoutProvider|MockObject $checkoutProvider;
    private ActionGroupRegistry|MockObject $actionGroupRegistry;
    private WebsiteManager|MockObject $websiteManager;
    private ManagerRegistry|MockObject $managerRegistry;
    private FeatureChecker|MockObject $featureChecker;

    protected function setUp(): void
    {
        $this->checkoutProvider = $this->createMock(ShoppingListCheckoutProvider::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProcessCheckoutOnSourceChange(
            $this->checkoutProvider,
            $this->actionGroupRegistry,
            $this->websiteManager,
            $this->managerRegistry
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_checkout.guest_checkout');
    }

    public function testOnInteractiveLogin(): void
    {
        $customerUser = new CustomerUser();

        $visitorShoppingList = new ShoppingList();
        $visitorCheckoutSource = new CheckoutSourceStub();
        $visitorCheckout = (new Checkout())->setSource($visitorCheckoutSource);

        $currentShoppingList = new ShoppingList();
        $currentCheckout = new Checkout();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutProvider
            ->expects($this->any())
            ->method('getCheckout')
            ->willReturnMap([
                [$visitorShoppingList, $visitorCheckout],
                [$currentShoppingList, $currentCheckout]
            ]);

        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup
            ->expects($this->once())
            ->method('execute');
        $this->actionGroupRegistry
            ->expects($this->once())
            ->method('findByName')
            ->with('actualize_checkout')
            ->willReturn($actionGroup);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('beginTransaction');
        $entityManager
            ->expects($this->once())
            ->method('commit');
        $entityManager
            ->expects($this->exactly(2))
            ->method('remove');
        $entityManager
            ->expects($this->exactly(3))
            ->method('flush');

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $shoppingListPostTransferEvent = new ShoppingListEventPostTransfer($visitorShoppingList, $currentShoppingList);
        $this->listener->onShoppingListPostTransfer($shoppingListPostTransferEvent);

        $event = new InteractiveLoginEvent($request, $token);
        $this->listener->onInteractiveLogin($event);
    }
}
