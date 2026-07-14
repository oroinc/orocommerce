<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads guest and registered checkouts with different "updated at" dates to test
 * the expired guest checkouts cleanup command.
 */
class LoadExpiredGuestCheckoutsData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string ANONYMOUS_CHECKOUT_EXPIRED = 'anonymous_checkout_expired';
    public const string ANONYMOUS_CHECKOUT_NOT_EXPIRED = 'anonymous_checkout_not_expired';
    public const string GUEST_USER_CHECKOUT_EXPIRED = 'guest_user_checkout_expired';
    public const string REGISTERED_CHECKOUT_EXPIRED = 'registered_checkout_expired';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $registeredCustomerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
        $owner = $manager->getRepository(User::class)->findOneBy([]);
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var Organization $organization */
        $organization = $registeredCustomerUser->getOrganization();

        $expiredDate = new \DateTime('-1 year', new \DateTimeZone('UTC'));
        $notExpiredDate = new \DateTime('now', new \DateTimeZone('UTC'));

        /** @var BaseUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $guestCustomerUser = new CustomerUser();
        $guestCustomerUser->setIsGuest(true)
            ->setEmail('guest_checkout_cleanup@example.com')
            ->setFirstName('Guest')
            ->setLastName('User')
            ->setEnabled(true)
            ->setOrganization($organization)
            ->setPlainPassword('guest_checkout_password');
        $userManager->updateUser($guestCustomerUser, false);

        $checkouts = [
            self::ANONYMOUS_CHECKOUT_EXPIRED     => [null, $expiredDate],
            self::ANONYMOUS_CHECKOUT_NOT_EXPIRED => [null, $notExpiredDate],
            self::GUEST_USER_CHECKOUT_EXPIRED    => [$guestCustomerUser, $expiredDate],
            self::REGISTERED_CHECKOUT_EXPIRED    => [$registeredCustomerUser, $expiredDate],
        ];

        foreach ($checkouts as $reference => [$customerUser, $date]) {
            $this->createCheckout($manager, $reference, $customerUser, $organization, $website, $owner, $date);
        }

        $manager->flush();
    }

    private function createCheckout(
        ObjectManager $manager,
        string $reference,
        ?CustomerUser $customerUser,
        Organization $organization,
        Website $website,
        User $owner,
        \DateTime $updatedAt
    ): void {
        $checkout = new Checkout();
        $checkout->setCustomerUser($customerUser);
        $checkout->setOrganization($organization);
        $checkout->setWebsite($website);
        $checkout->setOwner($owner);
        $checkout->setSource(new CheckoutSource());
        $checkout->setCompleted(false);
        $checkout->setUpdatedAt($updatedAt);

        $manager->persist($checkout);
        $this->setReference($reference, $checkout);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadWebsiteData::class];
    }
}
