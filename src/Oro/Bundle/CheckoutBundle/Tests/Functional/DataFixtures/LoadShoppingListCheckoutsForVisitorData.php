<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * Loads checkouts created from shopping lists to belongs to customer visitors.
 */
class LoadShoppingListCheckoutsForVisitorData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    const CHECKOUT_1 = 'checkout_1';
    const CHECKOUT_2 = 'checkout_2';
    const CHECKOUT_3 = 'checkout_3';

    const SHOPPING_LIST_1 = 'shopping_list_1';
    const SHOPPING_LIST_2 = 'shopping_list_2';
    const SHOPPING_LIST_3 = 'shopping_list_3';

    const CUSTOMER_VISITOR_1 = 'customer_visitor_1';
    const CUSTOMER_VISITOR_2 = 'customer_visitor_2';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createCustomerVisitor($manager, self::CUSTOMER_VISITOR_1)
            ->addShoppingList($this->createShoppingList($manager, self::SHOPPING_LIST_1));

        $this->createCustomerVisitor($manager, self::CUSTOMER_VISITOR_2)
            ->addShoppingList($this->createShoppingList($manager, self::SHOPPING_LIST_2));

        $this->createShoppingList($manager, self::SHOPPING_LIST_3);

        parent::load($manager);
    }

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        $paymentTermIdentifier = $this->getPaymentMethodIdentifier($this->container);

        return [
            self::CHECKOUT_1 => [
                'source'   => self::SHOPPING_LIST_1,
                'checkout' => ['payment_method' => $paymentTermIdentifier]
            ],
            self::CHECKOUT_2 => [
                'source'   => self::SHOPPING_LIST_2,
                'checkout' => ['payment_method' => $paymentTermIdentifier]
            ],
            self::CHECKOUT_3 => [
                'source'   => self::SHOPPING_LIST_3,
                'checkout' => ['payment_method' => $paymentTermIdentifier]
            ]
        ];
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_checkout';
    }

    /**
     * @return Checkout
     */
    protected function createCheckout()
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName()
    {
        return 'shoppingList';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadWebsiteData::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class
            ]
        );
    }

    /**
     * @param ObjectManager $manager
     * @param string        $reference
     *
     * @return CustomerVisitor
     */
    private function createCustomerVisitor(ObjectManager $manager, $reference)
    {
        $visitor = new CustomerVisitor();
        $visitor->setSessionId(md5(time()));

        $manager->persist($visitor);
        $this->addReference($reference, $visitor);

        return $visitor;
    }

    /**
     * @param ObjectManager $manager
     * @param string        $reference
     *
     * @return ShoppingList
     */
    private function createShoppingList(ObjectManager $manager, $reference)
    {
        $customerUser = $this->getDefaultCustomerUser($manager);

        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($customerUser->getOrganization());
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setCustomer($customerUser->getCustomer());
        $shoppingList->setLabel($reference . '_label');
        $shoppingList->setCurrent(true);
        $shoppingList->setWebsite($this->getReference(LoadWebsiteData::WEBSITE1));
        $manager->persist($shoppingList);
        $this->addReference($reference, $shoppingList);

        return $shoppingList;
    }
}
