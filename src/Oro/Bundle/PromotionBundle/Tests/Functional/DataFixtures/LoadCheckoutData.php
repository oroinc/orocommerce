<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCheckoutData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const PROMOTION_CHECKOUT_1 = 'promo_checkout_1';

    use ContainerAwareTrait;

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [LoadCustomerUserData::class, LoadShoppingListLineItemsData::class];
    }

    /**
     * @var array
     */
    protected $checkoutData = [
        self::PROMOTION_CHECKOUT_1 => [
            'name' => 'checkout1',
            'customerUserReference' => LoadCustomerUserData::EMAIL,
            'reference' => 'checkout_1',
            'sourceEntityReference' => LoadShoppingListsData::PROMOTION_SHOPPING_LIST,
            'shippingMethod' => 'flat-rate',
            'shippingMethodType' => 'air',
            'price' => [
                'value' => 20,
                'currency' => 'USD'
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->checkoutData as $reference => $data) {
            $entity = new Checkout();
            $checkoutSource = new CheckoutSource();
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($data['sourceEntityReference']);
            $checkoutSource->setShoppingList($shoppingList);

            $entity->setSource($checkoutSource);
            $entity->setCustomerUser($shoppingList->getCustomerUser());
            $entity->setOrganization($shoppingList->getOrganization());
            $entity->setWebsite($shoppingList->getWebsite());
            $entity->setCurrency('USD');
            $entity->setShippingCost(Price::create($data['price']['value'], $data['price']['currency']));
            $entity->setShippingMethod($data['shippingMethod']);
            $entity->setShippingMethodType($data['shippingMethodType']);

            $entity = $this->addCheckoutLineItems($entity);

            $this->setReference($reference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * Checkout line items should be added manually to Checkout
     *
     * @param Checkout $checkout
     *
     * @return Checkout
     */
    protected function addCheckoutLineItems(Checkout $checkout)
    {
        $lineItemsFactory = $this->container->get('oro_checkout.line_items.factory');
        $checkout->setLineItems($lineItemsFactory->create($checkout->getSourceEntity()));

        return $checkout;
    }
}
