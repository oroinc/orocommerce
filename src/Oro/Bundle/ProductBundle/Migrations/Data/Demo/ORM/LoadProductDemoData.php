<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Gaufrette\Adapter\Local;
use Gaufrette\File;
use Gaufrette\Filesystem;
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
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads demo products with images.
 */
class LoadProductDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * @var string
     */
    const ENUM_CODE_INVENTORY_STATUS = 'prod_inventory_status';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $productUnits = [];

    /**
     * @var array|Filesystem[]
     */
    protected $filesystems = [];

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadBrandDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        $outOfStockStatus = $this->getOutOfStockInventoryStatus($manager);

        $allImageTypes = $this->getImageTypes();
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);

        $this->container->get('oro_layout.loader.image_filter')->load();

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
                ->setInventoryStatus($outOfStockStatus)
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
    private function createSlugs(array $products, ObjectManager $manager)
    {
        $slugRedirectGenerator = $this->container->get('oro_redirect.generator.slug_entity');

        foreach ($products as $product) {
            $slugRedirectGenerator->generate($product, true);
        }

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCache) {
            $cache->flushAll();
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return AbstractEnumValue|object
     *
     * @throws \InvalidArgumentException
     */
    protected function getOutOfStockInventoryStatus(ObjectManager $manager)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName(self::ENUM_CODE_INVENTORY_STATUS);

        return $manager->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => Product::INVENTORY_STATUS_OUT_OF_STOCK
        ]);
    }

    /**
     * @param ObjectManager $manager
     * @param FileLocator $locator
     * @param string $sku
     * @param string $name
     * @param array|null $types
     * @return null|ProductImage
     */
    protected function getProductImageForProductSku(ObjectManager $manager, FileLocator $locator, $sku, $name, $types)
    {
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

            $this->writeFilteredDigitalAssets($file, $locator, $sku, $name);

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

    /**
     * @return array
     */
    protected function getImageTypes()
    {
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');

        return array_keys($imageTypeProvider->getImageTypes());
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return ProductUnit|null
     */
    protected function getProductUnit(EntityManager $manager, $code)
    {
        if (!array_key_exists($code, $this->productUnits)) {
            $this->productUnits[$code] = $manager->getRepository(ProductUnit::class)->find($code);
        }

        return $this->productUnits[$code];
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

    /**
     * @param Product $product
     * @param ObjectManager $manager
     * @param FileLocator $locator
     * @param string $sku
     * @param string $name
     * @param array $allImageTypes
     */
    private function addImageToProduct(
        $product,
        $manager,
        $locator,
        $sku,
        $name,
        $allImageTypes
    ) {
        $resizedImagePathProvider = $this->container->get('oro_attachment.provider.resized_image_path');
        $publicMediaCacheManager = $this->container->get('oro_attachment.manager.public_mediacache');

        $productImage = $this->getProductImageForProductSku($manager, $locator, $sku, $name, $allImageTypes);
        $imageDimensionsProvider = $this->container->get('oro_product.provider.product_images_dimensions');
        if ($productImage) {
            $product->addImage($productImage);

            foreach ($imageDimensionsProvider->getDimensionsForProductImage($productImage) as $dimension) {
                $filterName = $dimension->getName();
                $filesystem = $this->getFilesystem($locator, $filterName);

                $filteredImage = $this->getResizedProductImageFile($filesystem, $sku, $name);
                if (!$filteredImage) {
                    continue;
                }

                $storagePath = $resizedImagePathProvider
                    ->getPathForFilteredImage($productImage->getImage(), $filterName);
                $publicMediaCacheManager->writeToStorage($filteredImage->getContent(), $storagePath);
            }
        }
    }

    /**
     * @param AttachmentFile $file
     * @param FileLocator $locator
     * @param string $sku
     * @param string $name
     */
    private function writeFilteredDigitalAssets(AttachmentFile $file, $locator, $sku, $name): void
    {
        $resizedImagePathProvider = $this->container->get('oro_attachment.provider.resized_image_path');
        $cacheManager = $this->container->get('oro_attachment.manager.protected_mediacache');
        $filters = array_keys($this->container->getParameter('liip_imagine.filter_sets'));

        foreach ($filters as $filter) {
            if (strpos($filter, 'digital_asset_') !== 0) {
                continue;
            }

            $filesystem = $this->getFilesystem($locator, $filter);

            $filteredImage = $this->getResizedProductImageFile($filesystem, $sku, $name);
            if (!$filteredImage) {
                continue;
            }

            $storagePath = $resizedImagePathProvider->getPathForFilteredImage($file, $filter);
            $cacheManager->writeToStorage($filteredImage->getContent(), $storagePath);
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

    /**
     * @param Filesystem $filesystem
     * @param string $sku
     * @param string $name
     * @return null|File
     */
    protected function getResizedProductImageFile(Filesystem $filesystem, $sku, $name)
    {
        $file = null;

        try {
            $file = $filesystem->get(sprintf('%s.jpeg', $this->getImageFileName($sku, $name)));
        } catch (\Exception $e) {
            //image not found
        }

        return $file;
    }

    /**
     * @param FileLocator $locator
     * @param string $filterName
     * @return Filesystem
     */
    protected function getFilesystem(FileLocator $locator, $filterName)
    {
        if (!array_key_exists($filterName, $this->filesystems)) {
            $rootPath = $locator->locate(
                sprintf('@OroProductBundle/Migrations/Data/Demo/ORM/images/resized/%s', $filterName)
            );

            if (is_array($rootPath)) {
                $rootPath = current($rootPath);
            }

            $this->filesystems[$filterName] = new Filesystem(new Local($rootPath, false, 0600));
        }

        return $this->filesystems[$filterName];
    }

    /**
     * @param string $sku
     * @param string $name
     * @return string
     */
    protected function getImageFileName($sku, $name): string
    {
        return trim($sku . '-' . preg_replace('/\W+/', '-', $name), '-');
    }
}
