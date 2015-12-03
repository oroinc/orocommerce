<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductData extends AbstractFixture
{
    use UserUtilityTrait;

    const PRODUCT_1 = 'product.1';
    const PRODUCT_2 = 'product.2';
    const PRODUCT_3 = 'product.3';
    const PRODUCT_4 = 'product.4';
    const PRODUCT_5 = 'product.5';
    const PRODUCT_6 = 'product.6';
    const PRODUCT_7 = 'product.7';

    /**
     * @var array
     */
    protected $products = [
        [
            'productCode' => self::PRODUCT_1,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_IN_STOCK,
            'status' => Product::STATUS_ENABLED
        ],
        [
            'productCode' => self::PRODUCT_2,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_IN_STOCK,
            'status' => Product::STATUS_ENABLED
        ],
        [
            'productCode' => self::PRODUCT_3,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_OUT_OF_STOCK,
            'status' => Product::STATUS_ENABLED
        ],
        [
            'productCode' => self::PRODUCT_4,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_DISCONTINUED,
            'status' => Product::STATUS_ENABLED
        ],
        [
            'productCode' => self::PRODUCT_5,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_IN_STOCK,
            'status' => Product::STATUS_DISABLED
        ],
        [
            'productCode' => self::PRODUCT_6,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_IN_STOCK,
            'status' => Product::STATUS_ENABLED
        ],
        [
            'productCode' => self::PRODUCT_7,
            'inventoryStatus' =>  Product::INVENTORY_STATUS_IN_STOCK,
            'status' => Product::STATUS_ENABLED
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $enumInventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        foreach ($this->products as $item) {
            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setSku($item['productCode']);
            $name = new LocalizedFallbackValue();
            $name->setString($item['productCode']);
            $product->addName($name);
            $product->setInventoryStatus($inventoryStatuses[$item['inventoryStatus']]);
            $product->setStatus($item['status']);
            $description = new LocalizedFallbackValue();
            $description->setString($item['productCode']);
            $product->addDescription($description);
            $manager->persist($product);
            $this->addReference($item['productCode'], $product);
        }

        $manager->flush();
    }
}
