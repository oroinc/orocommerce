<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase as TestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\Finder\Finder;

/**
 * @dbIsolationPerTest
 */
class ProductImageImportTest extends TestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        // copy fixture files to the storage
        $fileManager = self::getContainer()->get('oro_product.tests.importexport.file_manager.product_images');
        $finder = new Finder();
        $files = $finder->files()->in(__DIR__.'/data/product_image/images/');
        /** @var \SplFileInfo[] $files */
        foreach ($files as $file) {
            $fileManager->writeFileToStorage($file->getPathname(), $file->getFilename());
        }

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testExportTemplate()
    {
        $this->assertExportTemplateWorks(
            $this->getExportImportConfiguration(),
            __DIR__.'/data/product_image/product_image_export_template.csv'
        );
    }

    public function testImportAddAndReplaceStrategy()
    {
        $this->assertImportWorks(
            $this->getExportImportConfiguration(),
            __DIR__.'/data/product_image/product_image_import.csv'
        );

        $this->assertImportedDataValid();
    }

    public function testImportAddAndReplaceStrategyWithTypes()
    {
        $this->assertImportWorks(
            $this->getExportImportConfiguration(),
            __DIR__.'/data/product_image/product_image_import_with_types.csv'
        );

        /** @var  EntityRepository $productRepo */
        $productRepo = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);

        /** @var Product $product */
        $product = $productRepo->find($this->getReference(LoadProductData::PRODUCT_1)->getId());

        $this->assertCount(4, $product->getImages());

        $this->assertProductImageTypes(['main', 'additional'], 'product-1_1.jpg', $product);
        $this->assertProductImageTypes(['listing', 'additional'], 'product-1_2.jpg', $product);
        $this->assertProductImageTypes(['additional'], 'product-1_3.jpg', $product);
    }

    private function assertProductImageTypes(array $expected, string $imageName, Product $product): void
    {
        $productImage = null;
        foreach ($product->getImages() as $image) {
            if ($image->getImage() && $image->getImage()->getOriginalFilename() === $imageName) {
                $productImage = $image;

                break;
            }
        }

        $this->assertNotNull($productImage);

        $types = array_map(
            static function (ProductImageType $productImageType) {
                return $productImageType->getType();
            },
            $productImage->getTypes()->toArray()
        );

        $this->assertEquals(array_combine($expected, $expected), $types);
    }

    private function assertImportedDataValid()
    {
        /** @var EntityRepository $productRepo */
        $productRepo = self::getContainer()->get('doctrine')->getRepository(Product::class);

        /** @var Product $product */
        $product = $productRepo->find($this->getReference(LoadProductData::PRODUCT_1)->getId());

        $this->assertCount(2, $product->getImages());
    }

    private function getExportImportConfiguration(): ImportExportConfigurationInterface
    {
        return $this->getContainer()->get('oro_product.importexport.configuration_provider.product_images')->get();
    }
}
