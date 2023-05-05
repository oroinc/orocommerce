<?php
declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File as AttachmentFile;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo products with images.
 */
class LoadProductDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const ENUM_CODE_INVENTORY_STATUS = 'prod_inventory_status';

    public const OUT_OF_STOCK_SKUS = ['0RT28', '1AB92', '1GB82', '1GS46', '1TB10'];

    protected array $productUnits = [];

    public function getDependencies(): array
    {
        return [
            LoadBrandDemoData::class,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $inStockStatus = static::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_IN_STOCK);
        $outOfStockStatus = static::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_OUT_OF_STOCK);
        $allImageTypes = $this->getImageTypes();
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        $slugGenerator = $this->container->get('oro_entity_config.slug.generator');
        $loadedProducts = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $name = new ProductName();
            $name->setString($row['name']);

            $text = '<p  class="product-view-desc">' . $row['description'] . '</p>'
                . (
                    array_key_exists('information', $row) && !empty($row['information']) ?
                    '<p class="product-view-desc-title">Product Information &amp; Features:</p>
                    <ul class="product-view-desc-list"><li class="product-view-desc-list__item">'
                    .   implode('</li><li class="product-view-desc-list__item">', explode("\n", $row['information']))
                    . '</li></ul>'
                    : ''
                )
                . (
                    array_key_exists('specifications', $row) && !empty($row['specifications']) ?
                    '<p class="product-view-desc-title">Technical Specs:</p>'
                    . '<ul class="product-view-desc-list"><li class="product-view-desc-list__item">'
                    .   implode('</li><li class="product-view-desc-list__item">', explode("\n", $row['specifications']))
                    . '</li></ul>'
                    : ''
                );

            $description = new ProductDescription();
            $description->setWysiwyg(nl2br($text));

            $shortDescription = new ProductShortDescription();
            $shortDescription->setText($row['description']);

            $product = new Product();
            $product->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setSku($row['sku'])
                ->setInventoryStatus(
                    \in_array($row['sku'], static::OUT_OF_STOCK_SKUS) ? $outOfStockStatus : $inStockStatus
                )
                ->setStatus(Product::STATUS_ENABLED)
                ->addName($name)
                ->addDescription($description)
                ->addShortDescription($shortDescription)
                ->setType($row['type'])
                ->setFeatured($row['featured'])
                ->setNewArrival($row['new_arrival']);

            if ($row['brand_id']) {
                $brand = $manager->getRepository(Brand::class)->find($row['brand_id']);

                if ($brand) {
                    $product->setBrand($brand);
                }
            }

            $this->setPageTemplate($product, $row);

            $slugPrototype = new LocalizedFallbackValue();
            $slugPrototype->setString($slugGenerator->slugify($row['name']));
            $product->addSlugPrototype($slugPrototype);

            $productUnit = $this->getProductUnit($manager, $row['unit']);

            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPrecision((int)$row['precision'])
                ->setConversionRate(1)
                ->setSell(true);

            $product->setPrimaryUnitPrecision($productUnitPrecision);

            $this->addImageToProduct($product, $manager, $locator, $row['sku'], $row['name'], $allImageTypes);

            $manager->persist($product);
            $loadedProducts[] = $product;
        }

        $manager->flush();

        fclose($handler);

        $this->createSlugs($loadedProducts, $manager);
    }

    /**
     * @param array|Product[] $products
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

    /**
     * Returns product inventory status enum value entity based on its value ID.
     *
     * Examples:
     *     $inStock = LoadProductDemoData::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_OUT_OF_STOCK);
     *     $outOfStock = LoadProductDemoData::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_IN_STOCK);
     *     $disc = LoadProductDemoData::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_DISCONTINUED);
     */
    public static function getProductInventoryStatus(ObjectManager $manager, string $status): ?AbstractEnumValue
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName(self::ENUM_CODE_INVENTORY_STATUS);

        return $manager->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => $status
        ]);
    }

    protected function getProductImageForProductSku(
        ObjectManager $manager,
        FileLocator $locator,
        string $sku,
        string $name,
        ?array $types
    ): ?ProductImage {
        $productImage = null;

        $user = $this->getFirstUser($manager);

        try {
            $fileName = $this->getImageFileName($sku, $name);
            $imagePath = $locator->locate(
                sprintf('@OroProductBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $fileName)
            );

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $fileManager = $this->container->get('oro_attachment.file_manager');
            $file = $fileManager->createFileEntity($imagePath);
            $file->setOwner($user);
            $manager->persist($file);

            $title = new LocalizedFallbackValue();
            $title->setString($sku);
            $manager->persist($title);

            $digitalAsset = new DigitalAsset();
            $digitalAsset->addTitle($title)
                ->setSourceFile($file)
                ->setOwner($user)
                ->setOrganization($user->getOrganization());
            $manager->persist($digitalAsset);

            $image = new AttachmentFile();
            $image->setDigitalAsset($digitalAsset);
            $manager->persist($image);
            $manager->flush();

            $productImage = new ProductImage();
            $productImage->setImage($image);
            foreach ($types as $type) {
                $productImage->addType($type);
            }
        } catch (\Exception $e) {
            //image not found
        }

        return $productImage;
    }

    protected function getImageTypes(): array
    {
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');

        return array_keys($imageTypeProvider->getImageTypes());
    }

    protected function getProductUnit(EntityManagerInterface $manager, string $code): ?ProductUnit
    {
        if (!array_key_exists($code, $this->productUnits)) {
            $this->productUnits[$code] = $manager->getRepository(ProductUnit::class)->find($code);
        }

        return $this->productUnits[$code];
    }

    private function getDefaultAttributeFamily(ObjectManager $manager): ?AttributeFamily
    {
        $familyRepository = $manager->getRepository(AttributeFamily::class);

        return $familyRepository->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }

    private function addImageToProduct(
        Product $product,
        ObjectManager $manager,
        FileLocator $locator,
        string $sku,
        string $name,
        array $allImageTypes
    ): void {
        $productImage = $this->getProductImageForProductSku($manager, $locator, $sku, $name, $allImageTypes);
        if ($productImage) {
            $product->addImage($productImage);
        }
    }

    /**
     * @param Product $product
     * @param array   $row
     * @return LoadProductDemoData
     */
    private function setPageTemplate(Product $product, array $row)
    {
        if (!empty($row['page_template'])) {
            $entityFallbackValue = new EntityFieldFallbackValue();
            $entityFallbackValue->setArrayValue([ProductType::PAGE_TEMPLATE_ROUTE_NAME => $row['page_template']]);

            $product->setPageTemplate($entityFallbackValue);
        }

        return $this;
    }

    protected function getImageFileName(string $sku, string $name): string
    {
        return trim($sku . '-' . preg_replace('/\W+/', '-', $name), '-');
    }
}
