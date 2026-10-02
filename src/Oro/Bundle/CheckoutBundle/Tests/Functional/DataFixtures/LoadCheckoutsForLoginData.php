<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * Loads the checkouts a registration may or may not claim: an ownerless one of a customer visitor and one
 * owned by a guest customer user.
 */
class LoadCheckoutsForLoginData extends AbstractFixture implements DependentFixtureInterface
{
    public const VISITOR_1 = 'bb_27963_visitor_1';

    public const VISITOR_SESSION_1 = 'bb_27963_visitor_session_1';
    public const VISITOR_SESSION_2 = 'bb_27963_visitor_session_2';

    public const SHOPPING_LIST_1 = 'bb_27963_shopping_list_1';
    public const SHOPPING_LIST_4 = 'bb_27963_shopping_list_4';

    public const CHECKOUT_VISITOR_1 = 'bb_27963_checkout_visitor_1';
    public const CHECKOUT_GUEST_CUSTOMER_USER = 'bb_27963_checkout_guest_customer_user';

    public const ANOTHER_CUSTOMER_USER = 'bb_27963_another_customer_user';
    public const GUEST_CUSTOMER_USER = 'bb_27963_guest_customer_user';

    public function load(ObjectManager $manager): void
    {
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $owner = $manager->getRepository(User::class)->findOneBy([]);

        $visitor1 = $this->createVisitor($manager, self::VISITOR_1, self::VISITOR_SESSION_1);

        $shoppingList1 = $this->createShoppingList($manager, self::SHOPPING_LIST_1, $website, $customerUser, null);
        $this->createAnotherCustomerUser($manager, $customerUser, $owner);

        $shoppingList4 = $this->createShoppingList($manager, self::SHOPPING_LIST_4, $website, $customerUser, null);

        $visitor1->addShoppingList($shoppingList1);
        $visitor1->addShoppingList($shoppingList4);

        $guestCustomerUser = $this->createGuestCustomerUser($manager, $customerUser, $owner);

        $this->createCheckout($manager, self::CHECKOUT_VISITOR_1, $website, $owner, $shoppingList1, null);
        $this->createCheckout(
            $manager,
            self::CHECKOUT_GUEST_CUSTOMER_USER,
            $website,
            $owner,
            $shoppingList4,
            $guestCustomerUser
        );

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadWebsiteData::class];
    }

    private function createAnotherCustomerUser(
        ObjectManager $manager,
        CustomerUser $organizationSource,
        User $owner
    ): CustomerUser {
        $customer = new Customer();
        $customer->setName('BB-27963 Another Customer');
        $customer->setOrganization($organizationSource->getOrganization());
        $customer->setOwner($owner);
        $manager->persist($customer);

        $customerUser = new CustomerUser();
        $customerUser->setFirstName('BB27963');
        $customerUser->setLastName('Another');
        $customerUser->setEmail('bb27963.another@example.com');
        $customerUser->setUsername('bb27963.another@example.com');
        $customerUser->setPassword('bb27963');
        $customerUser->setEnabled(true);
        $customerUser->setConfirmed(true);
        $customerUser->setOrganization($organizationSource->getOrganization());
        $customerUser->setOwner($owner);
        $customerUser->setWebsite($organizationSource->getWebsite());
        $customerUser->setCustomer($customer);
        $manager->persist($customerUser);
        $this->addReference(self::ANOTHER_CUSTOMER_USER, $customerUser);

        return $customerUser;
    }

    private function createGuestCustomerUser(
        ObjectManager $manager,
        CustomerUser $organizationSource,
        User $owner
    ): CustomerUser {
        $customer = new Customer();
        $customer->setName('BB-27963 Guest Customer');
        $customer->setOrganization($organizationSource->getOrganization());
        $customer->setOwner($owner);
        $manager->persist($customer);

        $customerUser = new CustomerUser();
        $customerUser->setFirstName('BB27963');
        $customerUser->setLastName('Guest');
        $customerUser->setEmail('bb27963.guest@example.com');
        $customerUser->setUsername('bb27963.guest@example.com');
        $customerUser->setPassword('bb27963');
        $customerUser->setIsGuest(true);
        $customerUser->setEnabled(false);
        $customerUser->setConfirmed(false);
        $customerUser->setOrganization($organizationSource->getOrganization());
        $customerUser->setOwner($owner);
        $customerUser->setWebsite($organizationSource->getWebsite());
        $customerUser->setCustomer($customer);
        $manager->persist($customerUser);
        $this->addReference(self::GUEST_CUSTOMER_USER, $customerUser);

        return $customerUser;
    }

    private function createVisitor(ObjectManager $manager, string $reference, string $sessionId): CustomerVisitor
    {
        $visitor = new CustomerVisitor();
        $visitor->setSessionId($sessionId);
        $manager->persist($visitor);
        $this->addReference($reference, $visitor);

        return $visitor;
    }

    private function createShoppingList(
        ObjectManager $manager,
        string $reference,
        Website $website,
        CustomerUser $organizationSource,
        ?CustomerUser $customerUser
    ): ShoppingList {
        $shoppingList = new ShoppingList();
        $shoppingList->setLabel($reference);
        $shoppingList->setWebsite($website);
        $shoppingList->setOrganization($organizationSource->getOrganization());
        if (null !== $customerUser) {
            $shoppingList->setCustomerUser($customerUser);
            $shoppingList->setCustomer($customerUser->getCustomer());
        }
        $manager->persist($shoppingList);
        $this->addReference($reference, $shoppingList);

        return $shoppingList;
    }

    private function createCheckout(
        ObjectManager $manager,
        string $reference,
        Website $website,
        User $owner,
        ShoppingList $shoppingList,
        ?CustomerUser $customerUser
    ): Checkout {
        $source = new CheckoutSource();
        $source->setShoppingList($shoppingList);
        $manager->persist($source);

        $checkout = new Checkout();
        $checkout->setSource($source);
        $checkout->setWebsite($website);
        $checkout->setOwner($owner);
        $checkout->setOrganization($website->getOrganization());
        $checkout->setCustomerNotes($reference);
        if (null !== $customerUser) {
            $checkout->setCustomerUser($customerUser);
        }
        $manager->persist($checkout);
        $this->addReference($reference, $checkout);

        return $checkout;
    }
}
