<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ResetCheckoutOnSourceChange;
use Oro\Bundle\CheckoutBundle\Provider\ShoppingListCheckoutProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class ResetCheckoutOnSourceChangeTest extends TestCase
{
    private ResetCheckoutOnSourceChange $listener;
    private ShoppingListCheckoutProvider|MockObject $checkoutProvider;
    private ActionGroupRegistry|MockObject $actionGroupRegistry;
    private WebsiteManager|MockObject $websiteManager;
    private FeatureChecker|MockObject $featureChecker;

    protected function setUp(): void
    {
        $this->checkoutProvider = $this->createMock(ShoppingListCheckoutProvider::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ResetCheckoutOnSourceChange(
            $this->checkoutProvider,
            $this->actionGroupRegistry,
            $this->websiteManager
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_checkout.guest_checkout');
    }

    public function testOnInteractiveLogin(): void
    {
        $visitorShoppingList = new ShoppingList();
        $currentShoppingList = new ShoppingList();
        $customerUser = new CustomerUser();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $checkout = new Checkout();
        $this->checkoutProvider
            ->expects($this->once())
            ->method('getCheckout')
            ->with($currentShoppingList)
            ->willReturn($checkout);

        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup
            ->expects($this->once())
            ->method('execute');
        $this->actionGroupRegistry
            ->expects($this->once())
            ->method('findByName')
            ->with('actualize_checkout')
            ->willReturn($actionGroup);

        $event = new InteractiveLoginEvent($request, $token);

        $shoppingListPostTransferEvent = new ShoppingListEventPostTransfer($currentShoppingList, $visitorShoppingList);
        $this->listener->onShoppingListPostTransfer($shoppingListPostTransferEvent);
        $this->listener->onInteractiveLogin($event);
    }
}
