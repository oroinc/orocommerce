<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gaufrette\Adapter as GaufretteAdapter;
use Gaufrette\File as GaufretteFile;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract base demo fixture for loading demo product images cache.
 */
abstract class AbstractLoadProductImagesCacheDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    protected ContainerInterface $container;

    /** @var GaufretteFilesystem[] */
    protected array $filesystems = [];

    abstract protected function getPathForFilteredImage(File $file, string $filter): string;

    abstract protected function getResizedProductImageFile(
        GaufretteFilesystem $filesystem,
        string $sku,
        string $name
    ): ?GaufretteFile;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function load(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        $this->container->get('oro_layout.loader.image_filter')->load();

        $productRepository = $manager->getRepository(Product::class);

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $product = $productRepository->findOneBySku($row['sku']);
            if (!$product) {
                continue;
            }

            foreach ($product->getImages() as $productImage) {
                /** @var DigitalAsset $digitalAsset */
                $digitalAsset = $productImage?->getImage()?->getDigitalAsset();
                if ($digitalAsset) {
                    $this->addDigitalAssetsImageCache(
                        $digitalAsset->getSourceFile(),
                        $locator,
                        $row['sku'],
                        $row['name']
                    );
                }

                $this->addProductImageCache($productImage, $locator, $row['sku'], $row['name']);
            }
        }

        $manager->flush();

        fclose($handler);
    }

    protected function addProductImageCache(
        ProductImage $productImage,
        FileLocator $locator,
        string $sku,
        string $name
    ): void {
        foreach ($this->getImageDimensionsProvider()->getDimensionsForProductImage($productImage) as $dimension) {
            $filterName = $dimension->getName();
            $filesystem = $this->getFilesystem($locator, $filterName);

            $filteredImage = $this->getResizedProductImageFile($filesystem, $sku, $name);
            if (!$filteredImage) {
                continue;
            }

            $storagePath = $this->getPathForFilteredImage($productImage->getImage(), $filterName);
            $this->getPublicMediaCacheManager()->writeToStorage($filteredImage->getContent(), $storagePath);
        }
    }

    private function addDigitalAssetsImageCache(
        File $file,
        FileLocator $locator,
        string $sku,
        string $name
    ): void {
        $filters = array_keys($this->container->getParameter('liip_imagine.filter_sets'));

        foreach ($filters as $filter) {
            if (!str_starts_with($filter, 'digital_asset_')) {
                continue;
            }

            $filesystem = $this->getFilesystem($locator, $filter);

            $filteredImage = $this->getResizedProductImageFile($filesystem, $sku, $name);
            if (!$filteredImage) {
                continue;
            }

            $storagePath = $this->getPathForFilteredImage($file, $filter);
            $this->getProtectedMediaCacheManager()->writeToStorage($filteredImage->getContent(), $storagePath);
        }
    }

    protected function getResizedImagePathProvider(): ResizedImagePathProviderInterface
    {
        return $this->container->get('oro_attachment.provider.resized_image_path');
    }

    protected function getImageDimensionsProvider(): ProductImagesDimensionsProvider
    {
        return $this->container->get('oro_product.provider.product_images_dimensions');
    }

    protected function getProtectedMediaCacheManager(): FileManager
    {
        return $this->container->get('oro_attachment.manager.protected_mediacache');
    }

    protected function getPublicMediaCacheManager(): FileManager
    {
        return $this->container->get('oro_attachment.manager.public_mediacache');
    }

    protected function getFilesystem(FileLocator $locator, string $filterName): GaufretteFilesystem
    {
        if (!array_key_exists($filterName, $this->filesystems)) {
            $rootPath = $locator->locate(
                sprintf('@OroProductBundle/Migrations/Data/Demo/ORM/images/resized/%s', $filterName)
            );

            if (is_array($rootPath)) {
                $rootPath = current($rootPath);
            }

            $this->filesystems[$filterName] = new GaufretteFilesystem(
                new GaufretteAdapter\Local($rootPath, false, 0600)
            );
        }

        return $this->filesystems[$filterName];
    }

    protected function getImageFileName(string $sku, string $name): string
    {
        return trim($sku . '-' . preg_replace('/\W+/', '-', $name), '-');
    }
}
