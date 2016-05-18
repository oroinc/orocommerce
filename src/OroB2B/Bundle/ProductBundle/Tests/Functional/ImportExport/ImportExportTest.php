<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @covers \OroB2B\Bundle\ProductBundle\ImportExport\TemplateFixture\ProductFixture
 */
class ImportExportTest extends WebTestCase
{
    /**
     * @var string
     */
    protected $file;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * Delete data required because there is commit to job repository in import/export controller action
     * Please use
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->rollback();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->getConnection()->clear();
     * if you don't use controller
     */
    protected function tearDown()
    {
        // clear DB from separate connection
        $batchJobManager = $this->getBatchJobManager();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobInstance')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobExecution')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:StepExecution')->execute();

        parent::tearDown();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getBatchJobManager()
    {
        /** @var BatchJobRepository $batchJobRepository */
        $batchJobRepository = $this->getContainer()->get('akeneo_batch.job_repository');

        return $batchJobRepository->getJobManager();
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testImportExport($strategy)
    {
        $this->validateImportFile($strategy);
        $this->doImport($strategy);

        $this->doExport();
        $this->validateExportResult();
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['orob2b_product_product.add_or_replace'],
        ];
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
                    'entity' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
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

    protected function doExport()
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'orob2b_product_product',
                    '_format' => 'json',
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['readsCount']);
        $this->assertEquals(0, $data['errorsCount']);

        $this->client->request(
            'GET',
            $data['url'],
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');
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
                'importInfo' => '1 entities were added, 0 entities were updated',
            ],
            $data
        );
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
                'orob2b_product_product_export_template',
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
     * @return string
     */
    protected function getExportFile()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->handleExport(
                JobExecutor::JOB_EXPORT_TO_CSV,
                'orob2b_product_product',
                ProcessorRegistry::TYPE_EXPORT
            );

        $result = json_decode($result->getContent(), true);
        $chains = explode('/', $result['url']);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }

    /**
     * @param string $fileName
     * @return array
     */
    protected function getFileContents($fileName)
    {
        $content = file_get_contents($fileName);
        $content = explode("\n", $content);
        $content = array_filter($content, 'strlen');

        return array_map('str_getcsv', $content);
    }

    protected function validateExportResult()
    {
        $importTemplate = $this->getFileContents($this->file);
        $exportedData = $this->getFileContents($this->getExportFile());

        $commonFields = array_intersect($importTemplate[0], $exportedData[0]);

        $importTemplateValues = $this->extractFieldValues($commonFields, $importTemplate);
        $exportedDataValues = $this->extractFieldValues($commonFields, $exportedData);

        $this->assertEquals($importTemplateValues, $exportedDataValues);
    }

    /**
     * @param array $fields
     * @param array $data
     * @return array
     */
    protected function extractFieldValues(array $fields, array $data)
    {
        // ID is changed
        // birthdays have different timestamps
        $skippedFields = ['Id', 'Birthday'];

        $values = [];
        foreach ($fields as $field) {
            if (!in_array($field, $skippedFields, true)) {
                $key = array_search($field, $data[0], true);
                if (false !== $key) {
                    $values[$field] = $data[1][$key];
                }
            }
        }

        return $values;
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
                'processorAlias' => 'orob2b_product_product.add_or_replace',
                'entityName' => $this->getContainer()->getParameter('orob2b_product.entity.product.class'),
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

    protected function cleanUpReader()
    {
        $reader = $this->getContainer()->get('oro_importexport.reader.csv');
        $reflection = new \ReflectionProperty(get_class($reader), 'file');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
        $reflection = new \ReflectionProperty(get_class($reader), 'header');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
    }

    /**
     * @return array
     */
    public function validationDataProvider()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import_validation.yml';

        return Yaml::parse(file_get_contents($filePath));
    }

    public function testImportRelations()
    {
        $token = new OrganizationToken(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
        );
        $token->setUser(
            $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->findOneBy([])
        );
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import.csv';

        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');
        $configuration = [
            'import' => [
                'processorAlias' => 'orob2b_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));
        $this->assertEmpty(
            $jobResult->getContext()->getErrors(),
            implode(PHP_EOL, $jobResult->getContext()->getErrors())
        );

        $em = $this->getContainer()->get('doctrine')->getManagerForClass($productClass);

        /** @var Product $product */
        $product = $em->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);
        $this->assertNotEmpty($product);
        $this->assertEquals('enabled', $product->getStatus());
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $this->assertCount(1, $product->getUnitPrecisions());
        $this->assertEquals('kg', $product->getUnitPrecisions()->first()->getUnit()->getCode());
        $this->assertEquals(3, $product->getUnitPrecisions()->first()->getPrecision());

        $this->assertCount(2, $product->getNames());
        $this->assertEquals('parent_locale', $product->getNames()->first()->getFallback());
        $this->assertEquals('Name', $product->getNames()->first()->getString());
        $this->assertEquals('system', $product->getNames()->last()->getFallback());
        $this->assertEquals('En Name', $product->getNames()->last()->getString());

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }
}
