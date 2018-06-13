<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Yaml\Yaml;

class LoadProductData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const PRODUCT_1 = 'product-1';
    const PRODUCT_2 = 'product-2';
    const PRODUCT_3 = 'product-3';
    const PRODUCT_4 = 'product-4';
    const PRODUCT_5 = 'product-5';
    const PRODUCT_6 = 'product-6';
    const PRODUCT_7 = 'product-7';
    const PRODUCT_8 = 'product-8';
    const PRODUCT_9 = 'product-9';

    const PRODUCT_1_DEFAULT_NAME = 'product-1.names.default';
    const PRODUCT_2_DEFAULT_NAME = 'product-2.names.default';
    const PRODUCT_3_DEFAULT_NAME = 'product-3.names.default';
    const PRODUCT_4_DEFAULT_NAME = 'product-4.names.default';
    const PRODUCT_5_DEFAULT_NAME = 'product-5.names.default';
    const PRODUCT_6_DEFAULT_NAME = 'product-6.names.default';
    const PRODUCT_7_DEFAULT_NAME = 'product-7.names.default';
    const PRODUCT_8_DEFAULT_NAME = 'product-8.names.default';
    const PRODUCT_9_DEFAULT_NAME = 'product-9.names.default';

    const PRODUCT_1_DEFAULT_SLUG_PROTOTYPE = 'product-1.slugPrototypes.default';
    const PRODUCT_2_DEFAULT_SLUG_PROTOTYPE = 'product-2.slugPrototypes.default';
    const PRODUCT_3_DEFAULT_SLUG_PROTOTYPE = 'product-3.slugPrototypes.default';
    const PRODUCT_4_DEFAULT_SLUG_PROTOTYPE = 'product-4.slugPrototypes.default';
    const PRODUCT_5_DEFAULT_SLUG_PROTOTYPE = 'product-5.slugPrototypes.default';
    const PRODUCT_6_DEFAULT_SLUG_PROTOTYPE = 'product-6.slugPrototypes.default';
    const PRODUCT_7_DEFAULT_SLUG_PROTOTYPE = 'product-7.slugPrototypes.default';
    const PRODUCT_8_DEFAULT_SLUG_PROTOTYPE = 'product-8.slugPrototypes.default';
    const PRODUCT_9_DEFAULT_SLUG_PROTOTYPE = 'product-9.slugPrototypes.default';

    const PRODUCTS_1_2_6_7 = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
    ];

    const PRODUCTS_1_2_3_6_7 = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
    ];

    const PRODUCTS_1_2_3_6_7_8_9 = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
        LoadProductData::PRODUCT_9
    ];

    const PRODUCTS_1_2_6_7_8_9 = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
        LoadProductData::PRODUCT_9,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadLocalizationData::class,
            LoadProductUnits::class,
            LoadAttributeFamilyData::class,
        ];
    }

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
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture.yml';

        $data = Yaml::parse(file_get_contents($filePath));
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);

        foreach ($data as $item) {
            $unit = $this->getReference('product_unit.milliliter');

            $unitPrecision = new ProductUnitPrecision();
            $unitPrecision->setUnit($unit)
                ->setPrecision((int)$item['primaryUnitPrecision']['precision'])
                ->setConversionRate(1)
                ->setSell(true);

            $product = new Product();
            $product
                ->setSku($item['productCode'])
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setInventoryStatus($inventoryStatuses[$item['inventoryStatus']])
                ->setStatus($item['status'])
                ->setPrimaryUnitPrecision($unitPrecision)
                ->setType($item['type'])
                ->setFeatured($item['featured']);

            if (isset($item['attributeFamily'])) {
                $product->setAttributeFamily($this->getReference($item['attributeFamily']));
            }

            $this->addAdvancedValue($item, $product);
            $this->addEntityFieldFallbackValue($item, $product);
            $this->addProductImages($item, $product);

            $manager->persist($product);
            $this->addReference($product->getSku(), $product);
            $this->addReference(
                sprintf('product_unit_precision.%s', implode('.', [$product->getSku(), $unit->getCode()])),
                $unitPrecision
            );
        }

        $manager->flush();
    }

    /**
     * @param array $name
     * @return LocalizedFallbackValue
     */
    protected function createValue(array $name)
    {
        $value = new LocalizedFallbackValue();
        if (array_key_exists('localization', $name)) {
            /** @var Localization $localization */
            $localization = $this->getReference($name['localization']);
            $value->setLocalization($localization);
        }
        if (array_key_exists('fallback', $name)) {
            $value->setFallback($name['fallback']);
        }
        if (array_key_exists('string', $name)) {
            $value->setString($name['string']);
        }
        if (array_key_exists('text', $name)) {
            $value->setText($name['text']);
        }
        $this->setReference($name['reference'], $value);

        return $value;
    }

    /**
     * @param array $name
     * @return EntityFieldFallbackValue
     */
    protected function createFieldFallbackValue(array $name)
    {
        $value = new EntityFieldFallbackValue();
        if (array_key_exists('fallback', $name)) {
            $value->setFallback($name['fallback']);
        }
        if (array_key_exists('scalarValue', $name)) {
            $value->setScalarValue($name['scalarValue']);
        }
        if (array_key_exists('arrayValue', $name)) {
            $value->setArrayValue($name['arrayValue']);
        }
        $this->setReference($name['reference'], $value);

        return $value;
    }


    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }

    /**
     * @param ObjectManager $manager
     * @return AttributeFamily|null
     */
    protected function getDefaultAttributeFamily(ObjectManager $manager)
    {
        $familyRepository = $manager->getRepository(AttributeFamily::class);

        return $familyRepository->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }

    /**
     * @param array $item
     * @param Product $product
     */
    private function addAdvancedValue(array $item, Product $product)
    {
        if (!empty($item['names'])) {
            foreach ($item['names'] as $slugPrototype) {
                $product->addName($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['slugPrototypes'])) {
            foreach ($item['slugPrototypes'] as $slugPrototype) {
                $product->addSlugPrototype($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['descriptions'])) {
            foreach ($item['descriptions'] as $slugPrototype) {
                $product->addDescription($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['shortDescriptions'])) {
            foreach ($item['shortDescriptions'] as $slugPrototype) {
                $product->addShortDescription($this->createValue($slugPrototype));
            }
        }
    }

    /**
     * @param array $item
     * @param Product $product
     */
    public function addProductImages(array $item, Product $product)
    {
        if (empty($item['images'])) {
            return;
        }

        foreach ($item['images'] as $image) {
            $imageFile = new File();
            $imageFile->setFilename($item['productCode']);
            $imageFile->setMimeType('image/jpeg');
            $this->setReference($image['reference'] . '.' . $item['productCode'], $imageFile);

            $productImage = new ProductImage();
            $productImage->setImage($imageFile);

            $productType = $image['type'] ?? ProductImageType::TYPE_LISTING;
            $productImage->addType($productType);

            $product->addImage($productImage);
        }
    }

    /**
     * @param array $item
     * @param Product $product
     */
    private function addEntityFieldFallbackValue(array $item, Product $product)
    {
        if (!empty($item['manageInventory'])) {
            $product->setManageInventory($this->createFieldFallbackValue($item['manageInventory']));
        }

        if (!empty($item['inventoryThreshold'])) {
            $product->setInventoryThreshold($this->createFieldFallbackValue($item['inventoryThreshold']));
        }

        if (!empty($item['minimumQuantityToOrder'])) {
            $product->setMinimumQuantityToOrder($this->createFieldFallbackValue($item['minimumQuantityToOrder']));
        }

        if (!empty($item['maximumQuantityToOrder'])) {
            $product->setMaximumQuantityToOrder($this->createFieldFallbackValue($item['maximumQuantityToOrder']));
        }

        if (!empty($item['decrementQuantity'])) {
            $product->setDecrementQuantity($this->createFieldFallbackValue($item['decrementQuantity']));
        }

        if (!empty($item['backOrder'])) {
            $product->setBackOrder($this->createFieldFallbackValue($item['backOrder']));
        }
    }
}
