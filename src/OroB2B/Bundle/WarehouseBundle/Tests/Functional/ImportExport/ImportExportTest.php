<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\ImportExport;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class ImportExportTest extends AbstractImportExportTestCase
{
    protected $inventoryStatusOnlyHeader = [
        'SKU',
        'Product',
        'Inventory Status',
    ];

    protected $inventoryLevelHeader = [
        'SKU',
        'Product',
        'Inventory Status',
        'Warehouse',
        'Quantity',
        'Unit',
    ];

    /**
     * @var string
     */
    protected $file;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWarehousesAndInventoryLevels::class]);
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testImportExport($strategy)
    {
        $this->validateImportFile($strategy);
        $this->doImport($strategy);
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['orob2b_warehouse.warehouse_inventory_level'],
        ];
    }

    /**
     * @param string $strategy
     */
    protected function doImport($strategy)
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => $strategy,
                    '_format' => 'json',
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'success'    => true,
                'message'    => 'File was successfully imported.',
                'errorsUrl'  => null,
                'importInfo' => '0 entities were added, 1 entities were updated',
            ],
            $data
        );
    }

    /**
     * @param string $strategy
     */
    protected function validateImportFile($strategy)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    'entity' => WarehouseInventoryLevel::class,
                    '_widgetContainer' => 'dialog',
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->file = $this->getImportTemplate();
        $this->assertTrue(file_exists($this->file));

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );

        $form['oro_importexport_import[file]']->upload($this->file);
        $form['oro_importexport_import[processorAlias]'] = $strategy;

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->getCrawler();
        $this->assertEquals(0, $crawler->filter('.import-errors')->count());
    }

    /**
     * @return string
     */
    protected function getImportTemplate()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->getExportResult(
                JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'orob2b_warehouse.inventory_level_export_template',
                ProcessorRegistry::TYPE_EXPORT_TEMPLATE
            );

        $chains = explode('/', $result['url']);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }

    /**
     * @dataProvider getExportTestInput
     */
    public function testExportInfluencedByProcessorChoice($exportChoice, $expectedHeader)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_config',
                $this->getDefaultRequestParameters()
            )
        );
        $form = $crawler->selectButton('Export')->form();
        $form['oro_importexport_export[detailLevel]'] = $exportChoice;
        $this->client->submit($form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);

        $file = $this->downloadFile($response['url']);
        $this->assertFileHeader($file, $expectedHeader);
        $this->assertFileContent($file, count($expectedHeader), $this->getExpectedRowsForExport($expectedHeader));
    }

    public function getExportTestInput()
    {
        return [
            ['orob2b_product.export_inventory_status_only', $this->inventoryStatusOnlyHeader],
            ['orob2b_warehouse.detailed_inventory_levels', $this->inventoryLevelHeader],
        ];
    }

    /**
     * @dataProvider getExportTemplateTestInput
     * @param string $exportChoice
     * @param [] $expectedHeader
     */
    public function testExportTemplateInventoryStatusOnly($exportChoice, $expectedHeader)
    {
        $this->client->useHashNavigation(false);
        $parameters = $this->getDefaultRequestParameters();
        $parameters['processorAlias'] = 'orob2b_warehouse.inventory_level_export_template';

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_template_config',
                $parameters
            )
        );
        $form = $crawler->selectButton('Download')->form();
        $form['oro_importexport_export_template[detailLevel]'] = $exportChoice;
        $this->client->submit($form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('url', $response);
        $this->assertContains('.csv', $response['url']);

        $file = $this->downloadFile($response['url']);
        $this->assertFileHeader($file, $expectedHeader);
    }

    public function getExportTemplateTestInput()
    {
        return [
            ['orob2b_product.inventory_status_only_export_template', $this->inventoryStatusOnlyHeader],
            ['orob2b_warehouse.inventory_level_export_template', $this->inventoryLevelHeader]
        ];
    }

    protected function downloadFile($url)
    {
        $this->client->request('GET', $url);

        /** @var File $csvFile */
        $csvFile = $this->client->getResponse()->getFile();
        $handle = fopen($csvFile->getRealPath(), "r");

        return $handle;
    }

    protected function assertFileHeader($csvFile, $expectedHeader)
    {
        $this->assertNotFalse($csvFile);
        $header = fgetcsv($csvFile);
        $this->assertEquals($expectedHeader, $header);
    }

    protected function assertFileContent($file, $numberOfColumns, $numberOfRows)
    {
        $row = fgetcsv($file);
        $rows = [];
        while ($row) {
            $this->assertEquals(count($row), $numberOfColumns);
            $rows[] = $row;
            $row = fgetcsv($file);
        }

        $this->assertEquals($numberOfRows, count($rows));

        return $rows;
    }

    protected function getDefaultRequestParameters()
    {
        return [
            '_widgetContainer' => 'dialog',
            '_wid' => uniqid('abc', true),
            'entity' => WarehouseInventoryLevel::class,
            'processorAlias' => 'orob2b_warehouse_detailed_inventory_levels'
        ];
    }


    protected function getExpectedRowsForExport($header)
    {
        if ($header == $this->inventoryStatusOnlyHeader) {
            return count(
                $this->client->getContainer()->get('oro_entity.doctrine_helper')
                    ->getEntityRepository(Product::class)
                    ->findAll()
            );
        }

        return count(
            $this->client->getContainer()->get('oro_entity.doctrine_helper')
                ->getEntityRepository(WarehouseInventoryLevel::class)
                ->findAll()
        );
    }

    /**
     * @param string $fileName
     * @param array $contextErrors
     *
     * @dataProvider validationDataProvider
     */
    public function testValidation($fileName, array $contextErrors = [])
    {
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $configuration = [
            'import_validation' => [
                'processorAlias' => 'orob2b_warehouse.warehouse_inventory_level',
                'entityName' => WarehouseInventoryLevel::class,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));

        // owner is not available in cli context, managed using ConsoleContextListener
        $errors = array_filter(
            $jobResult->getContext()->getErrors(),
            function ($error) {
                return strpos($error, 'owner: This value should not be blank.') === false;
            }
        );
        $this->assertEquals($contextErrors, array_values($errors), implode(PHP_EOL, $errors));
    }

    /**
     * @return array
     */
    public function validationDataProvider()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import_validation.yml';

        return Yaml::parse(file_get_contents($filePath));
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
        return 'import_status_data.yml';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportLevelFile()
    {
        return 'import_level_data.yml';
    }
}
