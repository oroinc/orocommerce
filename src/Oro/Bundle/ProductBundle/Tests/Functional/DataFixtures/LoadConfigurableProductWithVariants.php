<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class LoadConfigurableProductWithVariants extends AbstractFixture implements DependentFixtureInterface
{
    const CONFIGURABLE_SKU = 'PARENTCONFIG';
    const FIRST_VARIANT_SKU = 'FIRSTVARIANT';
    const SECOND_VARIANT_SKU = 'SECONDVARIANT';

    /** @var array */
    private $variants = [
        [
            'sku' => self::FIRST_VARIANT_SKU,
            'type' => Product::TYPE_SIMPLE,
            'name' => 'Good',
            'enumCodes' => ['first', 'second']
        ],
        [
            'sku' => self::SECOND_VARIANT_SKU,
            'type' => Product::TYPE_SIMPLE,
            'name' => 'Better',
            'enumCodes' => ['second', 'third']
        ],
    ];

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadUser::class,
            LoadProductUnits::class,
            LoadVariantFields::class,
            LoadProductMultiEnumValues::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $configurableProduct = $this->createProduct($manager, self::CONFIGURABLE_SKU, Product::TYPE_CONFIGURABLE);
        $configurableProduct->setVariantFields(['test_variant_field']);

        $this->setReference(self::CONFIGURABLE_SKU, $configurableProduct);

        foreach ($this->variants as $data) {
            $variant = $this->createProduct($manager, $data['sku'], $data['type'], $data['name'], $data['enumCodes']);

            $link = new ProductVariantLink($configurableProduct, $variant);
            $configurableProduct->addVariantLink($link);

            $manager->persist($link);

            $this->setReference($data['sku'], $variant);
        }

        $manager->flush();

        $this->container->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    /**
     * @param ObjectManager $manager
     * @param string $sku
     * @param string $type
     * @param null|string $variantName
     * @param array $multiEnumCodes
     * @return Product
     */
    private function createProduct(
        ObjectManager $manager,
        string $sku,
        string $type,
        ?string $variantName = null,
        array $multiEnumCodes = []
    ) {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $unit = $this->getReference(LoadProductUnits::BOX);

        $familyRepository = $manager->getRepository(AttributeFamily::class);
        $defaultProductFamily = $familyRepository
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        /** @var EnumOptionInterface $inventoryStatus */
        $inventoryStatus = $manager->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId('prod_inventory_status', 'in_stock'));

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit)
            ->setPrecision(0)
            ->setConversionRate(1)
            ->setSell(true);

        $product = new Product();
        $product
            ->setSku($sku)
            ->setOwner($businessUnit)
            ->setOrganization($organization)
            ->setAttributeFamily($defaultProductFamily)
            ->setInventoryStatus($inventoryStatus)
            ->setStatus(Product::STATUS_ENABLED)
            ->setPrimaryUnitPrecision($unitPrecision)
            ->setType($type);

        $defaultName = new ProductName();
        $defaultName->setString($sku);
        $product->addName($defaultName);

        if ($variantName) {
            $variantEnumRepository = $manager->getRepository(EnumOption::class);
            $variantEnum = $variantEnumRepository->findOneBy([
                'id' => ExtendHelper::buildEnumOptionId(
                    'variant_field_code',
                    ExtendHelper::buildEnumInternalId($variantName)
                )
            ]);
            $product->setTestVariantField($variantEnum);
        }

        if ($multiEnumCodes) {
            $multiEnumRepository = $manager->getRepository(EnumOption::class);
            foreach ($multiEnumCodes as $code) {
                $multiEnumOption = $multiEnumRepository->find(
                    ExtendHelper::buildEnumOptionId('multienum_code', $code)
                );
                $product->addMultienumField($multiEnumOption);
            }
        }

        $manager->persist($product);

        return $product;
    }
}
