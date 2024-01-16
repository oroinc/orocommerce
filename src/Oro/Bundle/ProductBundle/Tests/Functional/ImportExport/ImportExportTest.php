<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageProcessTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 *
 * @covers \Oro\Bundle\ProductBundle\ImportExport\TemplateFixture\ProductFixture
 */
class ImportExportTest extends AbstractImportExportTestCase
{
    use MessageProcessTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testShouldExportCorrectData()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13064)'
        );
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                ['processorAlias' => 'oro_product_product', '_format' => 'json']
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        self::assertCount(1, $data);
        self::assertTrue($data['success']);

        $exportedDataFilePath = $this->processExportMessage(self::getContainer(), $this->client);

        $expectedDataFilePath = self::getContainer()->get('file_locator')
            ->locate('@OroProductBundle/Tests/Functional/ImportExport/data/expected_export_product_row.csv');

        $exportedData = $this->getFileContents($exportedDataFilePath);
        $expectedData = $this->getFileContents($expectedDataFilePath);

        $commonFields = array_intersect($expectedData[0], $exportedData[0]);

        $expectedValues = $this->extractFieldValues($commonFields, $expectedData);
        $exportedValues = $this->extractFieldValues($commonFields, $exportedData);

        self::assertEquals($expectedValues, $exportedValues);

        unlink($exportedDataFilePath); // remove trash
    }

    /**
     * @dataProvider strategyDataProvider
     */
    public function testImportProcess(string $strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $importTemplateFile = $this->getImportTemplate();
        $this->validateImportFile($strategy, $importTemplateFile);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 1, 0);
    }

    public function strategyDataProvider(): array
    {
        return [
            'add or replace' => ['oro_product_product.add_or_replace'],
        ];
    }

    protected function getExportFile(): string
    {
        $result = self::getContainer()->get('oro_importexport.handler.export')
            ->getExportResult(JobExecutor::JOB_EXPORT_TO_CSV, 'oro_product_product');

        return self::getContainer()->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFileContents(string $fileName): array
    {
        $content = file_get_contents($fileName);
        $content = explode("\n", $content);
        $content = array_filter($content, 'strlen');

        return array_map('str_getcsv', $content);
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExportResult(string $exportFile, int $expectedItemsCount): void
    {
        $exportedData = $this->getFileContents($exportFile);
        unset($exportedData[0]);

        self::assertCount($expectedItemsCount, $exportedData);
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation(string $fileName, array $contextErrors = [])
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $configuration = [
            'import_validation' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => Product::class,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = self::getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        self::assertEmpty($exceptions, implode(PHP_EOL, $exceptions));

        // owner is not available in cli context, managed using ConsoleContextListener
        $errors = array_filter(
            $jobResult->getContext()->getErrors(),
            function ($error) {
                return
                    !str_contains($error, 'owner: This value should not be blank.')
                    && !str_contains($error, 'Unit of Quantity Unit Code: This value should not be blank.');
            }
        );
        self::assertEquals($contextErrors, array_values($errors), implode(PHP_EOL, $errors));
        self::getContainer()->get('security.token_storage')->setToken(null);
    }

    public function validationDataProvider(): array
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import_validation.yml';

        return Yaml::parse(file_get_contents($filePath));
    }

    public function testImportRelations()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import.csv';

        $productClass = Product::class;
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = self::getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        self::assertEmpty($exceptions, implode(PHP_EOL, $exceptions));
        self::assertEmpty(
            $jobResult->getContext()->getErrors(),
            implode(PHP_EOL, $jobResult->getContext()->getErrors())
        );

        /** @var Product $product */
        $product = self::getContainer()->get('doctrine')->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);

        self::assertNotEmpty($product);
        self::assertEquals('enabled', $product->getStatus());
        self::assertEquals('in_stock', $product->getInventoryStatus()->getId());

        self::assertCount(1, $product->getUnitPrecisions());
        self::assertEquals('each', $product->getUnitPrecisions()->first()->getUnit()->getCode());
        self::assertEquals(3, $product->getUnitPrecisions()->first()->getPrecision());

        self::assertCount(2, $product->getNames());
        self::assertEquals('parent_localization', $product->getNames()->first()->getFallback());
        self::assertEquals('Name', $product->getNames()->first()->getString());
        self::assertEquals('system', $product->getNames()->last()->getFallback());
        self::assertEquals('En Name', $product->getNames()->last()->getString());

        self::getContainer()->get('security.token_storage')->setToken(null);
    }

    public function testSkippedTypeForExistingProduct()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $token = new OrganizationToken(
            self::getContainer()->get('doctrine')->getRepository(Organization::class)->findOneBy([])
        );
        $token->setUser(
            self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([])
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        $this->cleanUpReader();

        $dataPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        $productClass = Product::class;
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $dataPath . 'import.csv',
            ],
        ];

        self::getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $this->cleanUpReader();

        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $dataPath . 'import_with_type.csv',
            ],
        ];

        self::getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        /** @var Product $product */
        $product = self::getContainer()->get('doctrine')->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);

        self::assertNotEmpty($product);
        self::assertNotEquals(Product::TYPE_CONFIGURABLE, $product->getType());
        self::assertEquals(Product::STATUS_DISABLED, $product->getStatus());

        self::getContainer()->get('security.token_storage')->setToken(null);
    }

    /**
     * @dataProvider strategyDataProvider
     */
    public function testAddNewProducts(string $strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests
             (see BAP-13063 and BAP-13064)'
        );
        $this->loadFixtures([LoadProductData::class]);
        $productClass = Product::class;

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $doctrine = self::getContainer()->get('doctrine');

        /** @var EntityManager $productManager */
        $productManager = $doctrine->getManagerForClass($productClass);
        $productManager->createQuery('DELETE FROM ' . Product::class)->execute();

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 8, 0);

        $products = $productManager->getRepository($productClass)->findAll();
        self::assertCount(8, $products);
    }

    /**
     * @dataProvider strategyDataProvider
     */
    public function testUpdateProducts(string $strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests
            (see BAP-13063 and BAP-13064)'
        );
        $this->loadFixtures([LoadProductData::class]);

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 0, 8);
    }

    /**
     * {@inheritDoc}
     */
    protected function assertImportResponse(array $data, int $added, int $updated): void
    {
        self::assertEquals(
            [
                'success'    => true,
                'message'    => 'File was successfully imported.',
                'importInfo' => $added . ' products were added, ' . $updated . ' products were updated',
            ],
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function setSecurityToken(): void
    {
        $token = new OrganizationToken(
            self::getContainer()->get('doctrine')->getRepository(Organization::class)->findOneBy([])
        );
        $token->setUser(
            self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([])
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }
}
