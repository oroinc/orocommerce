<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Command;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadExpiredGuestCheckoutsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class ClearExpiredGuestCheckoutsCommandTest extends WebTestCase
{
    private ObjectManager $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadExpiredGuestCheckoutsData::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Checkout::class);
    }

    public function testShouldClearOnlyExpiredGuestCheckouts(): void
    {
        $expiredAnonymous = $this->getReference(LoadExpiredGuestCheckoutsData::ANONYMOUS_CHECKOUT_EXPIRED);
        $notExpiredAnonymous = $this->getReference(LoadExpiredGuestCheckoutsData::ANONYMOUS_CHECKOUT_NOT_EXPIRED);
        $expiredGuestUser = $this->getReference(LoadExpiredGuestCheckoutsData::GUEST_USER_CHECKOUT_EXPIRED);
        $expiredRegistered = $this->getReference(LoadExpiredGuestCheckoutsData::REGISTERED_CHECKOUT_EXPIRED);

        $expiredAnonymousSourceId = $expiredAnonymous->getSource()->getId();
        $notExpiredAnonymousSourceId = $notExpiredAnonymous->getSource()->getId();
        $expiredGuestUserSourceId = $expiredGuestUser->getSource()->getId();
        $expiredRegisteredSourceId = $expiredRegistered->getSource()->getId();

        $result = $this->runCommand('oro:cron:checkout:clear-expired-guest-checkouts');
        self::assertStringContainsString('Clear expired guest checkouts completed', $result);

        $this->em->clear();

        self::assertNull($this->findCheckout($expiredAnonymous->getId()));
        self::assertNull($this->findCheckout($expiredGuestUser->getId()));
        self::assertNotNull($this->findCheckout($notExpiredAnonymous->getId()));
        self::assertNotNull($this->findCheckout($expiredRegistered->getId()));

        self::assertNull($this->findCheckoutSource($expiredAnonymousSourceId));
        self::assertNull($this->findCheckoutSource($expiredGuestUserSourceId));
        self::assertNotNull($this->findCheckoutSource($notExpiredAnonymousSourceId));
        self::assertNotNull($this->findCheckoutSource($expiredRegisteredSourceId));
    }

    private function findCheckout(int $id): ?Checkout
    {
        return $this->em->getRepository(Checkout::class)->find($id);
    }

    private function findCheckoutSource(int $id): ?CheckoutSource
    {
        return $this->em->getRepository(CheckoutSource::class)->find($id);
    }
}
