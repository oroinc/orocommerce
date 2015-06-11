<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\ImportExport;

use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ImportExportTest extends WebTestCase
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var PriceList
     */
    protected $priceList;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices']);

        $this->priceList = $this->getReference('price_list_1');
    }

    protected function tearDown()
    {
        // clear DB from separate connection
        $batchJobManager = $this->getBatchJobManager();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobInstance')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobExecution')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:StepExecution')->execute();
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
        $this->doExport();
        $this->file = $this->getExportFile();

        $this->validateImportFile($strategy);
        $crawler = $this->client->getCrawler();
        $this->assertEquals(0, $crawler->filter('.import-errors')->count());
        $this->doImport($strategy);
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testDuplicateRowsImport($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/duplicate_rows.csv',
            'Error in row #2. Product has duplication of product prices. '
            .'Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testInvalidCurrencyPriceListImport($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/invalid_currency.csv',
            'Error in row #1. price.currency: Currency "ARS" is not valid for current price list'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testInvalidProductUnit($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/invalid_product_unit.csv',
            'Error in row #1. unit: Unit "box" is not allowed for product "product.1".'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProductUnit($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/unavailable_product_unit.csv',
            'Error in row #1. unit: Product unit is not exist.'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProduct($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/unavailable_product.csv',
            'Error in row #1. product: Product is not exist.'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testNegativePriceValue($strategy)
    {
        $this->assertErrors(
            $strategy,
            '@OroB2BPricingBundle/Tests/Functional/ImportExport/data/negative_price_value.csv',
            'Error in row #1. price.value: This value should be 0 or more.'
        );
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['orob2b_pricing_product_price.add_or_replace'],
            'reset' => ['orob2b_pricing_product_price.reset']
        ];
    }

    /**
     * @param string $strategy
     * @param string $path
     * @param string $errorMessage
     */
    protected function assertErrors($strategy, $path, $errorMessage)
    {
        $locator = $this->getContainer()->get('file_locator');
        $this->file = $locator->locate($path);
        $this->validateImportFile($strategy);
        $crawler = $this->client->getCrawler();
        $this->assertEquals(1, $crawler->filter('.import-errors')->count());
        $this->assertContains($errorMessage, $crawler->filter('.import-errors')->html());
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
                    'entity' => 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice',
                    '_widgetContainer' => 'dialog',
                    'options[price_list_id]' => $this->priceList->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($strategy, $result->getContent());

        $this->assertFileExists($this->file);

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();

        $optionsPriceList = '&options[price_list_id]='. $this->priceList->getId() .
            '&importJob=price_list_product_prices_entity_import_from_csv' .
            '&exportJob=price_list_product_prices_export_to_csv';

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action'). $optionsPriceList . '&_widgetContainer=dialog'
        );

        $form['oro_importexport_import[file]']->upload($this->file);
        $form['oro_importexport_import[processorAlias]'] = $strategy;

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @param string $strategy
     */
    protected function doImport($strategy)
    {
        // test import
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => $strategy,
                    '_format' => 'json',
                    'options[price_list_id]' => $this->priceList->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'success'   => true,
                'message'   => 'File was successfully imported.',
                'errorsUrl' => null
            ],
            $data
        );
    }

    /**
     * export
     */
    protected function doExport()
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'orob2b_pricing_product_price',
                    '_format' => 'json',
                    'options[price_list_id]' => $this->priceList->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['readsCount']);
        $this->assertEquals(0, $data['errorsCount']);

        $this->client->request(
            'GET',
            $data['url']
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');
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
                'price_list_product_prices_export_to_csv',
                'orob2b_pricing_product_price',
                ProcessorRegistry::TYPE_EXPORT,
                'csv',
                null,
                ['price_list_id' => $this->priceList->getId()]
            );

        $result = json_decode($result->getContent(), true);
        $chains = explode('/', $result['url']);
        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }
}
