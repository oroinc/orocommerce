<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutsForLoginData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class CustomerUserListenerVisitorTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCheckoutsForLoginData::class]);
    }

    public function testCheckoutOfAnotherVisitorIsNotClaimed(): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_VISITOR_1);
        self::assertNull($checkout->getCustomerUser(), 'precondition: the checkout is ownerless');

        $this->registerWith($checkout->getId(), LoadCheckoutsForLoginData::VISITOR_SESSION_2);

        self::assertNull(
            $this->reloadCheckout($checkout->getId())->getRegisteredCustomerUser(),
            'a checkout of another visitor must not be claimed by the registering account'
        );
    }

    public function testCheckoutOfTheRegisteringVisitorIsClaimed(): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_VISITOR_1);

        $customerUser = $this->registerWith(
            $checkout->getId(),
            LoadCheckoutsForLoginData::VISITOR_SESSION_1
        );

        self::assertEquals(
            $customerUser->getId(),
            $this->reloadCheckout($checkout->getId())->getRegisteredCustomerUser()?->getId(),
            'the guest to registered migration keeps working for the own visitor'
        );
    }

    public function testGuestCustomerUserCheckoutOfTheRegisteringVisitorIsClaimed(): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutsForLoginData::CHECKOUT_GUEST_CUSTOMER_USER);
        self::assertTrue($checkout->getCustomerUser()->isGuest(), 'precondition: a guest customer user owns it');

        $customerUser = $this->registerWith(
            $checkout->getId(),
            LoadCheckoutsForLoginData::VISITOR_SESSION_1
        );

        self::assertEquals(
            $customerUser->getId(),
            $this->reloadCheckout($checkout->getId())->getRegisteredCustomerUser()?->getId(),
            'a guest owned checkout of the registering visitor is still claimed'
        );
    }

    private function registerWith(int $checkoutId, string $visitorSessionId): CustomerUser
    {
        $request = new Request(
            [],
            ['_checkout_registration' => '1', '_checkout_id' => $checkoutId],
            [],
            [
                AnonymousCustomerUserAuthenticator::COOKIE_NAME => base64_encode(
                    json_encode($visitorSessionId, JSON_THROW_ON_ERROR)
                )
            ]
        );
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        try {
            $customerUser = $this->createUnconfirmedCustomerUser();
            $listener = self::getContainer()->get('oro_checkout.event_listener.customer_user_register');
            $listener->afterFlush(
                new AfterFormProcessEvent($this->createMock(FormInterface::class), $customerUser)
            );
        } finally {
            $requestStack->pop();
        }

        return $customerUser;
    }

    private function createUnconfirmedCustomerUser(): CustomerUser
    {
        /** @var CustomerUser $source */
        $source = $this->getReference(LoadCheckoutsForLoginData::ANOTHER_CUSTOMER_USER);

        $customerUser = new CustomerUser();
        $customerUser->setFirstName('BB27963');
        $customerUser->setLastName('Registering');
        $customerUser->setEmail('bb27963.registering@example.com');
        $customerUser->setUsername('bb27963.registering@example.com');
        $customerUser->setPassword('bb27963');
        $customerUser->setConfirmed(false);
        $customerUser->setEnabled(true);
        $customerUser->setOrganization($source->getOrganization());
        $customerUser->setOwner($source->getOwner());
        $customerUser->setWebsite($source->getWebsite());
        $customerUser->setCustomer($source->getCustomer());

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CustomerUser::class);
        $entityManager->persist($customerUser);
        $entityManager->flush();

        return $customerUser;
    }

    private function reloadCheckout(int $checkoutId): Checkout
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class);
        $entityManager->clear();

        return $entityManager->getRepository(Checkout::class)->find($checkoutId);
    }
}
