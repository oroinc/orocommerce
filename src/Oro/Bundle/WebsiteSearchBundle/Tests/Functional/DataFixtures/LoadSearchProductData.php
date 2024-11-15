<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadSearchProductData extends AbstractFixture implements DependentFixtureInterface
{
    private const PRODUCTS_QUANTITY = 1001;

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadUser::class,
            LoadLocalizationData::class,
            LoadProductUnits::class,
            LoadAttributeFamilyData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        /** @var EnumOptionInterface[] $enumInventoryStatuses */
        $enumInventoryStatuses = $manager->getRepository(EnumOption::class)
            ->findBy(['enumCode' => Product::INVENTORY_STATUS_ENUM_CODE]);

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getInternalId()] = $inventoryStatus;
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
