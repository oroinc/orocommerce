<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads one enabled product per distinct product type, each with a real slug (not just a slug
 * prototype), for ProductPageRequestProviderTest functional coverage.
 */
class LoadProductPageRequestProviderData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const PRODUCT_SIMPLE = 'page-request-provider.product-simple';
    public const PRODUCT_CONFIGURABLE = 'page-request-provider.product-configurable';
    public const PRODUCT_KIT = 'page-request-provider.product-kit';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadProductUnits::class,
            LoadProductInventoryStatuses::class,
            LoadAttributeFamilyData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $attributeFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
        $unit = $this->getReference(LoadProductUnits::MILLILITER);
        $inventoryStatus = $this->getReference('in_stock');

        $productsData = [
            self::PRODUCT_SIMPLE => [Product::TYPE_SIMPLE, 'page-request-provider-simple'],
            self::PRODUCT_CONFIGURABLE => [Product::TYPE_CONFIGURABLE, 'page-request-provider-configurable'],
            self::PRODUCT_KIT => [Product::TYPE_KIT, 'page-request-provider-kit'],
        ];

        $products = [];
        foreach ($productsData as $referenceName => [$type, $sku]) {
            $name = str_replace('-', ' ', ucfirst($sku));

            $unitPrecision = (new ProductUnitPrecision())
                ->setUnit($unit)
                ->setPrecision(0)
                ->setConversionRate(1)
                ->setSell(true);

            $product = new Product();
            $product
                ->setSku($sku)
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($attributeFamily)
                ->setInventoryStatus($inventoryStatus)
                ->setStatus(Product::STATUS_ENABLED)
                ->setType($type)
                ->setPrimaryUnitPrecision($unitPrecision)
                ->addName((new ProductName())->setString($name))
                ->addSlugPrototype($this->createSlugPrototype($name));

            $manager->persist($product);
            $this->addReference($referenceName, $product);
            $products[] = $product;
        }

        $manager->flush();

        $this->createSlugs($products, $manager);
    }

    private function createSlugPrototype(string $productName): LocalizedFallbackValue
    {
        $slugPrototype = new LocalizedFallbackValue();
        $slug = $this->container->get('oro_entity_config.slug.generator')->slugify($productName);
        $slugPrototype->setString($slug);

        return $slugPrototype;
    }

    /**
     * @param Product[] $products
     */
    private function createSlugs(array $products, ObjectManager $manager): void
    {
        $slugEntityGenerator = $this->container->get('oro_redirect.generator.slug_entity');
        foreach ($products as $product) {
            $slugEntityGenerator->generate($product, true);
        }

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCacheInterface) {
            $cache->flushAll();
        }

        $manager->flush();
    }
}
