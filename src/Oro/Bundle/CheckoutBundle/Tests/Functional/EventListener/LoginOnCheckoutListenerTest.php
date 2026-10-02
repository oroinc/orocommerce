<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutListener;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutsForLoginData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @dbIsolationPerTest
 */
class LoginOnCheckoutListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private InteractiveAuthenticatorInterface&MockObject $authenticator;
    private TokenInterface&MockObject $token;
    private CustomerUser $customerUser;
    private mixed $initialGuestCheckout = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCheckoutsForLoginData::class]);

        $this->customerUser = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        $this->authenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $this->authenticator->expects(self::any())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token = $this->createMock(TokenInterface::class);
        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->initialGuestCheckout = self::getConfigManager()->get('oro_checkout.guest_checkout');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setGuestCheckout($this->initialGuestCheckout);

        parent::tearDown();
    }

    public function testCheckoutOfCurrentVisitorIsAdopted(): void
    {
        $this->setGuestCheckout(true);
        $checkoutId = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_VISITOR_1)->getId();

        $this->getListener()->onInteractiveLogin(new InteractiveLoginEvent(
            $this->createLoginRequest($checkoutId, LoadCheckoutsForLoginData::VISITOR_SESSION_1),
            $this->token
        ));

        $checkout = $this->reloadCheckout($checkoutId);
        self::assertNotNull($checkout);
        self::assertEquals($this->customerUser->getId(), $checkout->getCustomerUser()?->getId());
        self::assertEquals($this->customerUser->getCustomer()->getId(), $checkout->getCustomer()?->getId());
    }

    public function testGuestCustomerUserCheckoutOfCurrentVisitorIsAdopted(): void
    {
        $this->setGuestCheckout(true);
        $checkoutId = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_GUEST_CUSTOMER_USER)->getId();

        $this->getListener()->onInteractiveLogin(new InteractiveLoginEvent(
            $this->createLoginRequest($checkoutId, LoadCheckoutsForLoginData::VISITOR_SESSION_1),
            $this->token
        ));

        $checkout = $this->reloadCheckout($checkoutId);
        self::assertEquals($this->customerUser->getId(), $checkout->getCustomerUser()?->getId());
        self::assertEquals($this->customerUser->getCustomer()->getId(), $checkout->getCustomer()?->getId());
    }

    public function testOwnCheckoutIsContinuedAfterRegistrationDuringGuestCheckout(): void
    {
        $this->setGuestCheckout(true);
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_REGISTERED);
        $owner = $checkout->getCustomerUser();

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($owner);

        $this->getListener()->onInteractiveLogin(new InteractiveLoginEvent(
            $this->createLoginRequest($checkout->getId(), LoadCheckoutsForLoginData::VISITOR_SESSION_2),
            $token
        ));

        self::assertEquals(
            $owner->getId(),
            $this->reloadCheckout($checkout->getId())->getCustomerUser()?->getId(),
            'the own checkout stays with its owner and the login continues it'
        );
        self::assertEquals(
            $checkout->getId(),
            ReflectionUtil::getPropertyValue($this->getListener(), 'guestCheckoutId')
        );
    }

    public function testCheckoutOfAnotherVisitorIsNotAdopted(): void
    {
        $this->setGuestCheckout(true);
        $checkoutId = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_VISITOR_1)->getId();

        $this->getListener()->onInteractiveLogin(new InteractiveLoginEvent(
            $this->createLoginRequest($checkoutId, LoadCheckoutsForLoginData::VISITOR_SESSION_2),
            $this->token
        ));

        $checkout = $this->reloadCheckout($checkoutId);
        self::assertNotNull($checkout);
        self::assertNull($checkout->getCustomerUser());
        self::assertNull($checkout->getCustomer());
    }

    public function testCheckoutOfAnotherCustomerUserIsNeitherAdoptedNorReplaced(): void
    {
        $this->setGuestCheckout(true);
        /** @var Checkout $victimCheckout */
        $victimCheckout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_REGISTERED);
        $checkoutId = $victimCheckout->getId();
        $victimCustomerUserId = $victimCheckout->getCustomerUser()->getId();
        $request = $this->createLoginRequest($checkoutId, LoadCheckoutsForLoginData::VISITOR_SESSION_1);

        $listener = $this->getListener();
        $listener->onInteractiveLogin(new InteractiveLoginEvent($request, $this->token));

        $loginSuccessEvent = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $request,
            null,
            'test'
        );
        $listener->onCheckoutLogin($loginSuccessEvent);

        $checkout = $this->reloadCheckout($checkoutId);
        self::assertNotNull($checkout, 'The checkout of another customer user must survive the login');
        self::assertEquals($victimCustomerUserId, $checkout->getCustomerUser()?->getId());
        self::assertNull($loginSuccessEvent->getResponse());
    }

    private function getListener(): LoginOnCheckoutListener
    {
        return self::getContainer()->get('oro_checkout.event_listener.login_on_checkout');
    }

    private function createLoginRequest(int $checkoutId, string $visitorSessionId): Request
    {
        return new Request(
            [],
            ['_checkout_id' => $checkoutId],
            [],
            [
                AnonymousCustomerUserAuthenticator::COOKIE_NAME => base64_encode(
                    json_encode($visitorSessionId, JSON_THROW_ON_ERROR)
                )
            ]
        );
    }

    private function reloadCheckout(int $checkoutId): ?Checkout
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class);
        $entityManager->clear();

        return $entityManager->getRepository(Checkout::class)->find($checkoutId);
    }

    private function setGuestCheckout(mixed $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', $value);
        $configManager->flush();
    }
}
