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

    public const CHECKOUT_1 = 'checkout_1';
    public const CHECKOUT_2 = 'checkout_2';
    public const CHECKOUT_3 = 'checkout_3';

    public const SHOPPING_LIST_1 = 'shopping_list_1';
    public const SHOPPING_LIST_2 = 'shopping_list_2';
    public const SHOPPING_LIST_3 = 'shopping_list_3';

    public const CUSTOMER_VISITOR_1 = 'customer_visitor_1';
    public const CUSTOMER_VISITOR_2 = 'customer_visitor_2';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
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
    protected function getData(): array
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
     * {@inheritDoc}
     */
    protected function getWorkflowName(): string
    {
        return 'b2b_flow_checkout';
    }

    /**
     * {@inheritDoc}
     */
    protected function createCheckout(): Checkout
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName(): string
    {
        return 'shoppingList';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
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

    private function createCustomerVisitor(ObjectManager $manager, string $reference): CustomerVisitor
    {
        $visitor = new CustomerVisitor();
        $visitor->setSessionId(md5(time()));

        $manager->persist($visitor);
        $this->addReference($reference, $visitor);

        return $visitor;
    }

    private function createShoppingList(ObjectManager $manager, string $reference): ShoppingList
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
