<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutsForLoginData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @dbIsolationPerTest
 */
class LoginOnCheckoutOrderLimitListenerOwnershipTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private InteractiveAuthenticatorInterface&MockObject $authenticator;
    private mixed $initialGuestCheckout = null;
    private mixed $initialMinimumOrderAmount = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCheckoutsForLoginData::class]);

        $this->authenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $this->authenticator->expects(self::any())
            ->method('isInteractive')
            ->willReturn(true);

        $configManager = self::getConfigManager();
        $this->initialGuestCheckout = $configManager->get('oro_checkout.guest_checkout');
        $this->initialMinimumOrderAmount = $configManager->get('oro_checkout.minimum_order_amount');
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->set('oro_checkout.minimum_order_amount', [['value' => '5000', 'currency' => 'USD']]);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', $this->initialGuestCheckout);
        $configManager->set('oro_checkout.minimum_order_amount', $this->initialMinimumOrderAmount);
        $configManager->flush();

        parent::tearDown();
    }

    public function testCheckoutOfAnotherCustomerUserIsNotRemoved(): void
    {
        /** @var Checkout $victimCheckout */
        $victimCheckout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_REGISTERED);
        $checkoutId = $victimCheckout->getId();

        /** @var ShoppingList $source */
        $source = $this->getReference(LoadCheckoutsForLoginData::SHOPPING_LIST_3);
        self::assertFalse(
            self::getContainer()->get('oro_shopping_list.provider.order_limit')->isMinimumOrderAmountMet($source),
            'precondition: the order limit must be breached, otherwise the listener has nothing to act on'
        );

        /** @var CustomerUser $customerUser */
        $customerUser = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        self::assertNotEquals(
            $victimCheckout->getCustomerUser()->getId(),
            $customerUser->getId(),
            'the signing-in customer user must not own the checkout under test'
        );

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($customerUser);

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $token,
            new Request([], ['_checkout_id' => $checkoutId]),
            null,
            'test'
        );

        self::getContainer()->get('oro_checkout.event_listener.login_on_checkout_order_limit')
            ->onCheckoutLogin($event);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class);
        $entityManager->clear();

        self::assertNotNull(
            $entityManager->getRepository(Checkout::class)->find($checkoutId),
            'the checkout of another customer user must survive the login'
        );
        self::assertNull($event->getResponse(), 'no redirect to the source of a foreign checkout');
        self::assertFalse($event->isPropagationStopped());
    }
}
