<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @dbIsolationPerTest
 */
class LoginOnCheckoutOrderLimitListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use MessageQueueExtension;

    private InteractiveAuthenticatorInterface&MockObject $authenticator;
    private TokenInterface&MockObject $token;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadShoppingListsCheckoutsData::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
        ]);

        $this->authenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
    }

    /**
     * @dataProvider onCheckoutLoginDataProvider
     */
    public function testOnCheckoutLogin(
        ?string $minimumOrderAmount,
        ?string $maximumOrderAmount,
        bool $guestCheckout,
        bool $redirectExpected
    ): void {
        $listener = self::getContainer()->get('oro_checkout.event_listener.login_on_checkout_order_limit');

        /** @var CustomerUser $customerUser */
        $customerUser = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        $this->authenticator->expects($this->any())
            ->method('isInteractive')
            ->willReturn(true);
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        // Actual shopping list subtotal: 303.27 USD
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            new Request([], ['_checkout_id' => $checkout->getId()]),
            null,
            'test'
        );

        $configManager = self::getConfigManager();
        $initialGuestCheckout = $configManager->get('oro_checkout.guest_checkout');
        $initialMinimumOrderAmount = $configManager->get('oro_checkout.minimum_order_amount');
        $initialMaximumOrderAmount = $configManager->get('oro_checkout.maximum_order_amount');
        $configManager->set('oro_checkout.guest_checkout', $guestCheckout);
        $configManager->set(
            'oro_checkout.minimum_order_amount',
            [['value' => $minimumOrderAmount, 'currency' => 'USD']]
        );
        $configManager->set(
            'oro_checkout.maximum_order_amount',
            [['value' => $maximumOrderAmount, 'currency' => 'USD']]
        );
        $configManager->flush();
        try {
            $listener->onCheckoutLogin($event);
        } finally {
            $configManager->set('oro_checkout.guest_checkout', $initialGuestCheckout);
            $configManager->set('oro_checkout.minimum_order_amount', $initialMinimumOrderAmount);
            $configManager->set('oro_checkout.maximum_order_amount', $initialMaximumOrderAmount);
            $configManager->flush();
        }

        $checkout = self::getContainer()->get('doctrine')
            ->getRepository(Checkout::class)
            ->findOneById($checkout->getId());

        if ($redirectExpected) {
            $this->assertTrue($event->isPropagationStopped());
            $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
            $this->assertEquals(
                self::getContainer()->get('router')->generate(
                    'oro_shopping_list_frontend_update',
                    ['id' => $shoppingList->getId()]
                ),
                $event->getResponse()->getTargetUrl()
            );
            $this->assertNull($checkout);
        } else {
            $this->assertFalse($event->isPropagationStopped());
            $this->assertNotNull($checkout);
        }
    }

    public function onCheckoutLoginDataProvider(): array
    {
        // Actual shopping list subtotal: 303.27 USD
        return [
            'Minimum order amount not met' => [
                'minimumOrderAmount' => '310.00',
                'maximumOrderAmount' => null,
                'guestCheckout' => true,
                'redirectExpected' => true,
            ],
            'Maximum order amount not met' => [
                'minimumOrderAmount' => null,
                'maximumOrderAmount' => '300.00',
                'guestCheckout' => true,
                'redirectExpected' => true,
            ],
            'Minimum and maximum order amounts met' => [
                'minimumOrderAmount' => '300.00',
                'maximumOrderAmount' => '1000.00',
                'guestCheckout' => true,
                'redirectExpected' => false,
            ],
            'Guest checkout not enabled' => [
                'minimumOrderAmount' => '310.00',
                'maximumOrderAmount' => '1000.00',
                'guestCheckout' => false,
                'redirectExpected' => false,
            ],
        ];
    }
}
