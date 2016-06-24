<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\File\File;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class ImportExportControllerTest extends WebTestCase
{
    public static $inventoryStatusOnlyHeader = [
        'SKU',
        'Product',
        'Inventory status',
    ];

    public static $inventoryLevelHeader = [
        'SKU',
        'Product',
        'Inventory status',
        'Warehouse',
        'Quantity',
        'Unit',
    ];

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWarehousesAndInventoryLevels::class]);
    }

    /**
     * @dataProvider getExportTestInput
     */
    public function testExport($exportChoice, $expectedHeader)
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

        $this->assertFile($response['url'], $expectedHeader);
    }

    public function getExportTestInput()
    {
        return [
            ['export_inventory_status_only', self::$inventoryStatusOnlyHeader],
            ['orob2b_warehouse_detailed_inventory_levels', self::$inventoryLevelHeader],
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

        $this->assertFile($response['url'], $expectedHeader);
    }

    public function getExportTemplateTestInput()
    {
        return [
            ['orob2b_product.inventory_status_only_export_template', self::$inventoryStatusOnlyHeader],
            ['orob2b_warehouse.inventory_level_export_template', self::$inventoryLevelHeader]
        ];
    }

    protected function assertFile($url, $expectedHeader)
    {
        $this->client->request('GET', $url);

        /** @var File $csvFile */
        $csvFile = $this->client->getResponse()->getFile();
        $handle = fopen($csvFile->getRealPath(), "r");
        $this->assertNotFalse($handle);
        $header = fgetcsv($handle);

        $this->assertEquals($expectedHeader, $header);
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
}
