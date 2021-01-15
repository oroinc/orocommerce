<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadSearchProductData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    private const PRODUCTS_QUANTITY = 1001;

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

        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);
        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultAttributeFamily);

        for ($i = 0; $i < self::PRODUCTS_QUANTITY; ++$i) {
            $unit = $this->getReference('product_unit.milliliter');

            $unitPrecision = new ProductUnitPrecision();
            $unitPrecision->setUnit($unit)
                ->setPrecision(0)
                ->setConversionRate(1)
                ->setSell(true);

            $sku = sprintf('PSKU%d', $i);
            $product = new Product();
            $product
                ->setSku($sku)
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setInventoryStatus($inventoryStatuses['in_stock'])
                ->setStatus('enabled')
                ->setPrimaryUnitPrecision($unitPrecision)
                ->setType('simple');

            $value = (new ProductName())->setString($sku);
            $product->addName($value);

            $manager->persist($product);
        }

        $manager->flush();
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
}
