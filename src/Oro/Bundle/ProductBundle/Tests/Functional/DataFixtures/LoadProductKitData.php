<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads products kits.
 */
class LoadProductKitData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const PRODUCT_KIT_1 = 'product-kit-1';
    public const PRODUCT_KIT_2 = 'product-kit-2';
    public const PRODUCT_KIT_3 = 'product-kit-3';

    /** @var ProductUnit[] */
    private array $productUnits = [];

    /** @var Product[] */
    private array $products = [];

    public function getDependencies(): array
    {
        return [
            LoadProductUnits::class,
            LoadProductUnitPrecisions::class,
            LoadProductData::class,
        ];
    }

    private function getProductsData(): array
    {
        return [
            [
                'sku' => self::PRODUCT_KIT_1,
                'name' => 'Product Kit with Single Kit Item',
                'unit' => 'milliliter',
                'kitItems' => [
                    [
                        'label' => 'PKSKU1 - Unit of Quantity Taken from Product Kit',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 1,
                        'minimumQuantity' => null,
                        'maximumQuantity' => null,
                        'products' => ['product-1'],
                    ],
                ],
            ],
            [
                'sku' => self::PRODUCT_KIT_2,
                'name' => 'Product Kit Utilizing Sort Order',
                'unit' => 'milliliter',
                'kitItems' => [
                    [
                        'label' => 'PKSKU2 - Sort Order 1',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 1,
                        'minimumQuantity' => null,
                        'maximumQuantity' => null,
                        'products' => ['product-1', 'product-2'],
                    ],
                    [
                        'label' => 'PKSKU2 - Sort Order 2',
                        'unit' => 'milliliter',
                        'optional' => true,
                        'sortOrder' => 2,
                        'minimumQuantity' => null,
                        'maximumQuantity' => null,
                        'products' => ['product-3'],
                    ],
                ],
            ],
            [
                'sku' => self::PRODUCT_KIT_3,
                'name' => 'Product Kit Utilizing Min and Max Quantity',
                'unit' => 'milliliter',
                'kitItems' => [
                    [
                        'label' => 'PKSKU3 - With Min and Max Quantity',
                        'unit' => 'liter',
                        'optional' => false,
                        'sortOrder' => 1,
                        'minimumQuantity' => 1,
                        'maximumQuantity' => 2,
                        'products' => ['product-1', 'product-2'],
                    ],
                    [
                        'label' => 'PKSKU3 - With Min Quantity',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 2,
                        'minimumQuantity' => 2,
                        'maximumQuantity' => null,
                        'products' => ['product-3'],
                    ],
                    [
                        'label' => 'PKSKU3 - With Max Quantity',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 2,
                        'minimumQuantity' => null,
                        'maximumQuantity' => 4,
                        'products' => ['product-4', 'product-5'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function load(ObjectManager $manager): void
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);
        $inventoryStatus = $this->getOutOfStockInventoryStatus($manager);
        $createdProducts = [];

        foreach ($this->getProductsData() as $productData) {
            $productKit = new Product();
            $productKit
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setType(Product::TYPE_KIT)
                ->setSku($productData['sku'])
                ->addName((new ProductName())->setString($productData['name']))
                ->setStatus(Product::STATUS_ENABLED)
                ->setInventoryStatus($inventoryStatus)
                ->addSlugPrototype($this->createSlugPrototype($productData['name']))
                ->setPrimaryUnitPrecision(
                    $this->createProductUnitPrecision($manager, $productKit, $productData['unit'])
                );

            foreach ($productData['kitItems'] as $index => $kitItemData) {
                $kitItem = (new ProductKitItem())
                    ->addLabel((new ProductKitItemLabel())->setString($kitItemData['label']))
                    ->setOptional($kitItemData['optional'])
                    ->setSortOrder($kitItemData['sortOrder'])
                    ->setMinimumQuantity($kitItemData['minimumQuantity'])
                    ->setMaximumQuantity($kitItemData['maximumQuantity'])
                    ->setProductUnit($this->getProductUnit($manager, $kitItemData['unit']));

                foreach ($kitItemData['products'] as $kitItemProductSku) {
                    $kitItem->addKitItemProduct(
                        (new ProductKitItemProduct())
                            ->setProduct($this->getProduct($manager, $kitItemProductSku))
                    );
                }

                $productKit->addKitItem($kitItem);

                $this->setReference($productData['sku'] . '-kit-item-' . $index, $kitItem);
            }

            $manager->persist($productKit);

            $this->setReference($productData['sku'], $productKit);

            $createdProducts[] = $productKit;
        }

        $manager->flush();

        $this->createSlugs($createdProducts, $manager);
    }

    private function createSlugPrototype(string $productName): LocalizedFallbackValue
    {
        $slugPrototype = new LocalizedFallbackValue();
        $slug = $this->container->get('oro_entity_config.slug.generator')->slugify($productName);
        $slugPrototype->setString($slug);

        return $slugPrototype;
    }

    private function createProductUnitPrecision(
        ObjectManager $manager,
        Product $product,
        string $unitCode
    ): ProductUnitPrecision {
        $productUnit = $this->getProductUnit($manager, $unitCode);

        return (new ProductUnitPrecision())
            ->setProduct($product)
            ->setUnit($productUnit)
            ->setPrecision(0)
            ->setConversionRate(1)
            ->setSell(true);
    }

    /**
     * @param Product[] $products
     * @param ObjectManager $manager
     */
    private function createSlugs(array $products, ObjectManager $manager): void
    {
        $slugRedirectGenerator = $this->container->get('oro_redirect.generator.slug_entity');

        foreach ($products as $product) {
            $slugRedirectGenerator->generate($product, true);
        }

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCacheInterface) {
            $cache->flushAll();
        }

        $manager->flush();
    }

    private function getOutOfStockInventoryStatus(ObjectManager $manager): AbstractEnumValue
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        return $manager->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
        ]);
    }

    private function getProduct(EntityManager $manager, string $sku): ?Product
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    private function getProductUnit(EntityManager $manager, ?string $code): ?ProductUnit
    {
        if ($code === null) {
            return null;
        }

        if (!array_key_exists($code, $this->productUnits)) {
            $this->productUnits[$code] = $manager->getRepository(ProductUnit::class)->find($code);
        }

        return $this->productUnits[$code];
    }

    private function getDefaultAttributeFamily(ObjectManager $manager): AttributeFamily
    {
        return $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }
}
