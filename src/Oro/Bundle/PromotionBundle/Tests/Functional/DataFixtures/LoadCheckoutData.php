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
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadCheckoutData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const PROMOTION_CHECKOUT_1 = 'promo_checkout_1';

    private array $checkoutData = [
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

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCustomerUserData::class, LoadShoppingListLineItemsData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
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
            $lineItemsFactory = $this->container->get('oro_checkout.line_items.factory');
            $entity->setLineItems($lineItemsFactory->create($entity->getSourceEntity()));

            $this->setReference($reference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
