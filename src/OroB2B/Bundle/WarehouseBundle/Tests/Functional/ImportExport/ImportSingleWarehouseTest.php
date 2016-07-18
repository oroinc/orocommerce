<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadSingleWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class ImportSingleWarehouseTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadSingleWarehousesAndInventoryLevels::class]);
    }

    /**
     * @param string $fileName
     * @param array $configuration
     *
     * @dataProvider inventoryStatusDataProvider
     */
    public function testImportInventoryStatuses($fileName)
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $jobResult = $this->makeImport($filePath);
        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));
        $this->assertEmpty(
            $jobResult->getContext()->getErrors(),
            implode(PHP_EOL, $jobResult->getContext()->getErrors())
        );

        $file = fopen($filePath, "r");
        $header = fgetcsv($file);

        if (!$header) {
            return;
        }

        /** @var EntityRepository $repository */
        $repository = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Product::class);

        $row = fgetcsv($file);
        while ($row) {
            $values = array_combine($header, $row);
            $entity = $repository->findOneBy(['sku' => $values['SKU']]);

            $this->assertTrue($this->assertFields(
                $entity,
                $values,
                array_intersect($this->getFieldMappings(), $header),
                []
            ));

            $row = fgetcsv($file);
        }
    }

    /**
     * @param string $fileName
     * @param array $configuration
     *
     * @dataProvider inventoryLevelsDataProvider
     */
    public function testImportInventoryLevels($fileName)
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $jobResult = $this->makeImport($filePath);
        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));
        $this->assertEmpty(
            $jobResult->getContext()->getErrors(),
            implode(PHP_EOL, $jobResult->getContext()->getErrors())
        );

        $file = fopen($filePath, "r");
        $header = fgetcsv($file);

        if (!$header) {
            return;
        }

        $row = fgetcsv($file);
        while ($row) {
            $values = array_combine($header, $row);

            $this->assertTrue($this->assertFields(
                $this->getInventoryLevelEntity($values),
                $values,
                array_intersect($this->getFieldMappings(), $header),
                []
            ));

            $row = fgetcsv($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getImportStatusFile()
    {
        return 'import_status_data_single.yml';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportLevelFile()
    {
        return 'import_level_data_single.yml';
    }
}
