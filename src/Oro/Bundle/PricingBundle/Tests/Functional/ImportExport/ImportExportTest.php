<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportExportTest extends AbstractImportExportTestCase
{
    use MessageQueueExtension;

    /** @var string */
    private $file;

    /** @var PriceList */
    private $priceList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceListToProductWithoutPrices::class
        ]);

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
        $this->getOneSentMessageWithTopic(PreExportTopic::getName());

        $this->assertMessageProcessorExecuted();
        $this->assertMessageSent(ExportTopic::getName());

        $exportMessageData = $this->getOneSentMessageWithTopic(ExportTopic::getName());
        $this->assertMessageProcessorExecuted();

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
     * @dataProvider strategyDataProvider
     */
    public function testImportProcess(string $strategy, int $expectedAdd, int $expectedUpdate)
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
     * @dataProvider strategyDataProvider
     */
    public function testDuplicateRowsImport(string $strategy)
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
     * @dataProvider strategyDataProvider
     */
    public function testInvalidCurrencyPriceListImport(string $strategy)
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
     * @dataProvider strategyDataProvider
     */
    public function testInvalidProductUnit(string $strategy)
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
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProductUnit(string $strategy)
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
     * @dataProvider strategyDataProvider
     */
    public function testUnavailableProduct(string $strategy)
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
     * @dataProvider strategyDataProvider
     */
    public function testNegativePriceValue(string $strategy)
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

    public function strategyDataProvider(): array
    {
        return [
            'add or replace' => ['oro_pricing_product_price.add_or_replace', 0, 8],
            'reset' => ['oro_pricing_product_price.reset', 8, 0]
        ];
    }

    private function assertErrors(string $strategy, string $path, string $errorMessage): void
    {
        $locator = $this->getContainer()->get('file_locator');
        $this->file = $locator->locate($path);
        $this->validateImportFile($strategy);
        $crawler = $this->client->getCrawler();
        $this->assertEquals(1, $crawler->filter('.import-errors')->count());
        static::assertStringContainsString($errorMessage, $crawler->filter('.import-errors')->html());
    }

    private function validateImportFile(string $strategy): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    'entity' => ProductPrice::class,
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

    private function doImport(string $strategy, int $expectedAdd, int $expectedUpdate): void
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

    private function getExportFile(): string
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

        $result = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    private function processExportMessage(): string
    {
        $processedMessages = $this->consumeMessages(null, PreExportTopic::getName());

        foreach ($processedMessages as $processedMessage) {
            $this->assertEquals(MessageProcessorInterface::ACK, $processedMessage['context']->getStatus());
        }

        $sentMessages = $this->getSentMessages();
        foreach ($sentMessages as $messageData) {
            if (SendEmailNotificationTemplateTopic::getName() === $messageData['topic']) {
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
