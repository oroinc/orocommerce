<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\MessageQueue\Transport\Message;
use Symfony\Component\DomCrawler\Form;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportExportTest extends AbstractImportExportTestCase
{
    use MessageQueueExtension;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var PriceList
     */
    protected $priceList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices'
            ]
        );

        $this->priceList = $this->getReference('price_list_1');
    }

    public function testShouldExportData()
    {
        $this->client->followRedirects(false);
        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'oro_pricing_product_price',
                    '_format' => 'json',
                    'options[price_list_id]' => $this->getReference('price_list_1')->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $data);
        $this->assertTrue($data['success']);

        $content = $this->processExportMessage();
        $content = str_getcsv($content, "\n", '"', '"');
        $this->assertCount(9, $content);
    }

    public function testCountEntitiesThatWillBeExportedInOnePriceList(): void
    {
        $priceListId = $this->getReference('price_list_1')->getId();
        $this->assertPreExportActionExecuted($this->getExportImportConfiguration());
        $preExportMessageData = $this->getOneSentMessageWithTopic(ImportExportTopics::PRE_EXPORT);

        $this->assertMessageProcessorExecuted('oro_importexport.async.pre_export', $preExportMessageData);
        $this->assertMessageSent(ImportExportTopics::EXPORT);

        $exportMessageData = $this->getOneSentMessageWithTopic(ImportExportTopics::EXPORT);
        $this->assertMessageProcessorExecuted('oro_importexport.async.export', $exportMessageData);

        // We have only 8 prices that are related to the price_list_1
        $this->assertCount(8, $exportMessageData['options']['ids']);
        $this->assertEquals($priceListId, $exportMessageData['options']['price_list_id']);
        $this->clearMessageCollector();
    }

    public function testShouldExportCorrectDataWithRelations()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13064)'
        );
        $this->client->followRedirects(false);
        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'oro_pricing_product_price',
                    '_format' => 'json',
                    'options[price_list_id]' => $this->getReference('price_list_2')->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $data);
        $this->assertTrue($data['success']);

        $filePath = $this->processExportMessage();
        $locator = $this->getContainer()->get('file_locator');
        $this->assertFileEquals(
            $locator->locate(
                '@OroPricingBundle/Tests/Functional/ImportExport/data/expected_export_with_relations.csv'
            ),
            $filePath
        );

        unlink($filePath); // remove trash
    }

    /**
     * @param string $strategy
     * @param int $expectedAdd
     * @param int $expectedUpdate
     * @dataProvider strategyDataProvider
     */
    public function testImportProcess($strategy, $expectedAdd, $expectedUpdate)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->file = $this->getExportFile();

        $this->validateImportFile($strategy);
        $crawler = $this->client->getCrawler();
        $this->assertEquals(0, $crawler->filter('.import-errors')->count());
        $this->doImport($strategy, $expectedAdd, $expectedUpdate);

        unlink($this->file); // remove trash
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testDuplicateRowsImport($strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/duplicate_rows.csv',
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
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/invalid_currency.csv',
            'Error in row #1. price.currency: Currency "ARS" is not valid for current price list'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testInvalidProductUnit($strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/invalid_product_unit.csv',
            'Error in row #1. Unit Code: Unit "box" is not allowed for product "product.1".'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProductUnit($strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/unavailable_product_unit.csv',
            'Error in row #1. Unit Code: Product unit does not exist.'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProduct($strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/unavailable_product.csv',
            'Error in row #1. Product SKU: Product does not exist.'
        );
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testNegativePriceValue($strategy)
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->assertErrors(
            $strategy,
            '@OroPricingBundle/Tests/Functional/ImportExport/data/negative_price_value.csv',
            'Error in row #1. price.value: This value should be 0 or more.'
        );
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['oro_pricing_product_price.add_or_replace', 0, 8],
            'reset' => ['oro_pricing_product_price.reset', 8, 0]
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
        static::assertStringContainsString($errorMessage, $crawler->filter('.import-errors')->html());
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
                    'entity' => 'Oro\Bundle\PricingBundle\Entity\ProductPrice',
                    '_widgetContainer' => 'dialog',
                    'options[price_list_id]' => $this->priceList->getId(),
                    'importJob' => 'price_list_product_prices_entity_import_from_csv',
                    'exportJob' => 'price_list_product_prices_export_to_csv'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString($strategy, $result->getContent());

        $this->assertFileExists($this->file);

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();

        $optionsPriceList = '&options[price_list_id]='. $this->priceList->getId() .
            '&importJob=price_list_product_prices_entity_import_from_csv' .
            '&exportJob=price_list_product_prices_export_to_csv';

        /** Change after BAP-1813 */
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
     * @param int $expectedAdd
     * @param int $expectedUpdate
     */
    protected function doImport($strategy, $expectedAdd, $expectedUpdate)
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
                'success' => true,
                'message' => 'File was successfully imported.',
                'importInfo' => sprintf(
                    '%s product prices were added, %s product prices were updated',
                    $expectedAdd,
                    $expectedUpdate
                ),
            ],
            $data
        );
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
                'oro_pricing_product_price',
                ProcessorRegistry::TYPE_EXPORT,
                'csv',
                null,
                ['price_list_id' => $this->priceList->getId()]
            );

        $result = json_decode($result->getContent(), true);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    /**
     * @return string
     */
    protected function processExportMessage()
    {
        $sentMessage = $this->getSentMessage(ImportExportTopics::PRE_EXPORT);

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody(json_encode($sentMessage));

        /** @var ExportMessageProcessor $processor */
        $processor = $this->getContainer()->get('oro_importexport.async.pre_export');
        $processorResult = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $processorResult);

        $sentMessages = $this->getSentMessages();
        foreach ($sentMessages as $messageData) {
            if (Topics::SEND_NOTIFICATION_EMAIL === $messageData['topic']) {
                break;
            }
        }
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        preg_match('/http.*\.csv/', $messageData['message']['body'], $match);
        $urlChunks = explode('/', $match[0]);
        $filename = end($urlChunks);

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', ['fileName' => $filename]),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv; charset=UTF-8');

        return $result->getContent();
    }

    private function getExportImportConfiguration(): ImportExportConfiguration
    {
        $priceListId = $this->getReference('price_list_1')->getId();

        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price',
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'price_list_product_prices_export_to_csv',
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS => ['price_list_id' => $priceListId],
        ]);
    }
}
