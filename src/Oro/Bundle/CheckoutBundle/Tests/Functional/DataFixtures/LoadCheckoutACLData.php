<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCheckoutACLData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    const CHECKOUT_ACC_1_USER_LOCAL = 'checkout_customer1_user_local';
    const CHECKOUT_ACC_1_USER_BASIC = 'checkout_customer1_user_basic';
    const CHECKOUT_ACC_1_USER_DEEP = 'checkout_customer1_user_deep';

    const CHECKOUT_ACC_1_1_USER_LOCAL = 'checkout_customer1.1_user_local';

    const CHECKOUT_ACC_2_USER_LOCAL = 'checkout_customer2_user_local';

    /**
     * @var array
     */
    protected static $checkouts = [
        self::CHECKOUT_ACC_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::CHECKOUT_ACC_1_USER_BASIC => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::CHECKOUT_ACC_1_USER_DEEP => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::CHECKOUT_ACC_1_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
        self::CHECKOUT_ACC_2_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_2_ROLE_LOCAL
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCheckoutUserACLData::class,
            LoadShoppingLists::class,
            LoadWebsiteData::class,
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        /* @var WorkflowManager $workflowManager */
        $workflowManager = $this->container->get('oro_workflow.manager');

        foreach (self::$checkouts as $name => $checkout) {
            $checkout = $this->createOrder($manager, $name, $checkout);

            $workflowManager->startWorkflow('b2b_flow_checkout', $checkout);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $checkoutData
     * @return Checkout
     */
    protected function createOrder(ObjectManager $manager, $name, array $checkoutData)
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference($checkoutData['customerUser']);
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($customerUser->getOrganization())
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser)
            ->setLabel('test');
        $manager->persist($shoppingList);

        $source = new CheckoutSource();
        /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
        $source->setShoppingList($shoppingList);
        $manager->persist($source);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $checkout = new Checkout();
        $checkout
            ->setSource($source)
            ->setWebsite($website)
            ->setCurrency('USD')
            ->setOrganization($customerUser->getOrganization())
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser);
        $manager->persist($checkout);
        $manager->flush();
        $this->addReference($name, $checkout);

        return $checkout;
    }
}
