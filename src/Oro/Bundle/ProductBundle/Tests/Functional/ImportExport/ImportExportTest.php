<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolationPerTest
 *
 * @covers \Oro\Bundle\ProductBundle\ImportExport\TemplateFixture\ProductFixture
 */
class ImportExportTest extends AbstractImportExportTest
{
    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testImportExport($strategy)
    {
        $importTemplateFile = $this->getImportTemplate();
        $this->validateImportFile($strategy, $importTemplateFile);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 1, 0);

        $this->doExport();
        $this->validateExportResultWithImportTemplate($importTemplateFile);
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['oro_product_product.add_or_replace'],
        ];
    }

    /**
     * @param string $fileName
     * @param array $contextErrors
     *
     * @dataProvider validationDataProvider
     */
    public function testValidation($fileName, array $contextErrors = [])
    {
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $configuration = [
            'import_validation' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $this->getContainer()->getParameter('oro_product.entity.product.class'),
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
                return strpos($error, 'owner: This value should not be blank.') === false
                && strpos($error, 'Unit of Quantity Unit Code: This value should not be blank.') === false;
            }
        );
        $this->assertEquals($contextErrors, array_values($errors), implode(PHP_EOL, $errors));
        $this->getContainer()->get('security.token_storage')->setToken(null);
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
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import.csv';

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
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
        $this->assertEquals('each', $product->getUnitPrecisions()->first()->getUnit()->getCode());
        $this->assertEquals(3, $product->getUnitPrecisions()->first()->getPrecision());

        $this->assertCount(2, $product->getNames());
        $this->assertEquals('parent_localization', $product->getNames()->first()->getFallback());
        $this->assertEquals('Name', $product->getNames()->first()->getString());
        $this->assertEquals('system', $product->getNames()->last()->getFallback());
        $this->assertEquals('En Name', $product->getNames()->last()->getString());

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    public function testSkippedTypeForExistingProduct()
    {
        $token = new OrganizationToken(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
        );
        $token->setUser(
            $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->findOneBy([])
        );
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $this->cleanUpReader();

        $dataPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $dataPath . 'import.csv',
            ],
        ];

        $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
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

        $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $em = $this->getContainer()->get('doctrine')->getManagerForClass($productClass);

        /** @var Product $product */
        $product = $em->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);

        $this->assertNotEmpty($product);
        $this->assertNotEquals(Product::TYPE_CONFIGURABLE, $product->getType());
        $this->assertEquals(Product::STATUS_DISABLED, $product->getStatus());

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    /**
     * @dataProvider strategyDataProvider
     * @param string $strategy
     */
    public function testAddNewProducts($strategy)
    {
        $this->loadFixtures([LoadProductData::class]);
        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $doctrine = $this->getContainer()->get('doctrine');

        /** @var EntityManager $productManager */
        $productManager = $doctrine->getManagerForClass($productClass);
        $productManager->createQuery('DELETE FROM OroProductBundle:Product')->execute();

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 8, 0);

        $products = $productManager->getRepository($productClass)->findAll();
        $this->assertCount(8, $products);
    }

    /**
     * @dataProvider strategyDataProvider
     * @param string $strategy
     */
    public function testUpdateProducts($strategy)
    {
        $this->loadFixtures([LoadProductData::class]);

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 0, 8);
    }
}
