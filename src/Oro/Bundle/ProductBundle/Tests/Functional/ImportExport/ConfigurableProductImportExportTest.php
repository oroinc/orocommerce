<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyForConfigurableImportData;

class ConfigurableProductImportExportTest extends AbstractImportExportTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadAttributeFamilyForConfigurableImportData::class]);
    }

    public function testImport(): void
    {
        $this->assertImportWorks(
            $this->getExportImportConfiguration(),
            __DIR__.'/data/import_configurable_product.csv'
        );

        // Consume postponed message for configurable product import after variants import
        $this->consumeAllMessages();

        $this->assertImportedDataValid();
    }

    private function assertImportedDataValid(): void
    {
        /** @var EntityRepository $productRepo */
        $productRepo = self::getContainer()->get('doctrine')->getRepository(Product::class);

        $this->assertCount(3, $productRepo->findAll());

        /** @var Product $configurableProduct */
        $configurableProduct = $productRepo->findOneBySku('configurable');
        $this->assertNotNull($configurableProduct);
        $this->assertCount(2, $configurableProduct->getVariantLinks());
        $this->assertNotNull($configurableProduct->getDefaultVariant());
        $this->assertEquals('variant2', $configurableProduct->getDefaultVariant()->getSku());
    }

    private function getExportImportConfiguration(): ImportExportConfigurationInterface
    {
        return $this->getContainer()->get('oro_product.importexport.configuration_provider.product')->get();
    }
}
