<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadShoppingListLineItemsData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const LINE_ITEM_1 = 'promo_sl_line_item_1';

    /**
     * @var array
     */
    protected $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingListsData::PROMOTION_SHOPPING_LIST,
            'unit' => 'product_unit.liter',
            'quantity' => 5
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadShoppingListsData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->lineItems as $name => $lineItem) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($lineItem['shoppingList']);

            /** @var ProductUnit $unit */
            $unit = $this->getReference($lineItem['unit']);

            /** @var Product $product */
            $product = $this->getReference($lineItem['product']);

            $owner = $this->getFirstUser($manager);
            $item = new LineItem();
            $item->setNotes('Test Notes')
                ->setCustomerUser($shoppingList->getCustomerUser())
                ->setOrganization($shoppingList->getOrganization())
                ->setOwner($owner)
                ->setShoppingList($shoppingList)
                ->setUnit($unit)
                ->setProduct($product)
                ->setQuantity($lineItem['quantity']);

            $manager->persist($item);
            $this->addReference($name, $item);
        }

        $manager->flush();
    }
}
