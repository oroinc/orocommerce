<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract class for load Products fixtures
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
abstract class AbstractLoadProductData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use UserUtilityTrait;
    use ContainerAwareTrait;

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
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $data = Yaml::parse(file_get_contents($this->getFilePath()));
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);
        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultAttributeFamily);

        foreach ($data as $referenceName => $item) {
            if (isset($item['user'])) {
                /** @var User $user */
                $user = $this->getReference($item['user']);
            } else {
                /** @var EntityManager $manager */
                $user = $this->getFirstUser($manager);
            }

            $businessUnit = $user->getOwner();
            $organization = $user->getOrganization();

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
                ->setFeatured($item['featured'] ?? false);

            if (isset($item['attributeFamily'])) {
                $product->setAttributeFamily($this->getReference($item['attributeFamily']));
            }

            $this->addAdvancedValue($item, $product);
            $this->addEntityFieldFallbackValue($item, $product);
            $this->addProductImages($referenceName, $item, $product);

            $manager->persist($product);
            $this->addReference($referenceName, $product);
            $this->addReference(
                'product_unit_precision.' . $referenceName . '.' . $unit->getCode(),
                $unitPrecision
            );
        }

        $manager->flush();
    }

    abstract protected function getFilePath(): string;

    /**
     * @param array $name
     * @param string $className
     * @return AbstractLocalizedFallbackValue
     */
    protected function createValue(array $name, string $className = LocalizedFallbackValue::class)
    {
        /** @var AbstractLocalizedFallbackValue $value */
        $value = new $className();
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
        if (array_key_exists('wysiwyg', $name)) {
            $value->setWysiwyg($name['wysiwyg']);
        }
        $this->setReference($name['reference'], $value);

        return $value;
    }

    /**
     * @param array $name
     * @return EntityFieldFallbackValue
     */
    private function createFieldFallbackValue(array $name)
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
     * @param ObjectManager $manager
     * @return AttributeFamily|null
     */
    private function getDefaultAttributeFamily(ObjectManager $manager)
    {
        $familyRepository = $manager->getRepository(AttributeFamily::class);

        return $familyRepository->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }

    private function addAdvancedValue(array $item, Product $product)
    {
        if (!empty($item['names'])) {
            foreach ($item['names'] as $name) {
                $product->addName($this->createValue($name, ProductName::class));
            }
        }

        if (!empty($item['slugPrototypes'])) {
            foreach ($item['slugPrototypes'] as $slugPrototype) {
                $product->addSlugPrototype($this->createValue($slugPrototype));
            }
        }

        if (!empty($item['descriptions'])) {
            foreach ($item['descriptions'] as $description) {
                $product->addDescription($this->createValue($description, ProductDescription::class));
            }
        }

        if (!empty($item['shortDescriptions'])) {
            foreach ($item['shortDescriptions'] as $shortDescription) {
                $product->addShortDescription($this->createValue($shortDescription, ProductShortDescription::class));
            }
        }
    }

    private function addProductImages(string $productReferenceName, array $item, Product $product)
    {
        if (empty($item['images'])) {
            return;
        }

        $fileManager = $this->container->get('oro_attachment.file_manager');
        foreach ($item['images'] as $image) {
            $fileName = $productReferenceName . '.jpg';
            if (is_file(__DIR__. '/files/' . $fileName)) {
                $imageFile = $fileManager->createFileEntity(__DIR__ . '/files/' . $fileName);
            } else {
                $imageFile = new File();
                $imageFile->setFilename($fileName);
            }

            $imageFile->setOriginalFilename($productReferenceName . '-original.jpg');
            $imageFile->setExtension('jpg');
            $imageFile->setParentEntityClass(ProductImage::class);
            $imageFile->setMimeType('image/jpeg');
            $imageFile->setOwner($product->getOwner());
            $this->setReference($image['reference'] . '.' . $productReferenceName, $imageFile);

            $productImage = new ProductImage();
            $productImage->setImage($imageFile);

            $productType = $image['type'] ?? ProductImageType::TYPE_LISTING;
            $productImage->addType($productType);

            $product->addImage($productImage);
        }
    }

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

        if (!empty($item['highlightLowInventory'])) {
            $product->setHighlightLowInventory($this->createFieldFallbackValue($item['highlightLowInventory']));
        }

        if (!empty($item['isUpcoming'])) {
            $product->setIsUpcoming($this->createFieldFallbackValue($item['isUpcoming']));
        }
    }
}
