<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\PhpUtils\ArrayUtil;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class WarehouseControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels'
            ]
        );
    }

    /**
     * @dataProvider warehousePagesDataProvider
     *
     * @param array $data
     * @param bool $setSystemConfig
     * @param bool $isSystem
     */
    public function testWarehousePages(array $data, $setSystemConfig, $isSystem)
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_warehouse_update', ['id' => $warehouse->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            ArrayUtil::arrayMergeRecursiveDistinct(
                $form->getPhpValues(),
                [
                    'orob2b_warehouse' => [
                        'shipping_origin_warehouse' => $data
                    ]
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Warehouse has been saved', $crawler->html());
        $this->assertShippingOrigin($warehouse, $setSystemConfig, $isSystem);
    }

    /**
     * @return array
     */
    public function warehousePagesDataProvider()
    {
        return [
            [
                'data' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-NY',
                    'postalCode' => 'test code',
                    'city' => 'test city',
                    'street' => 'test street',
                ],
                'setSystemConfig' => true,
                'isSystem' => false
            ],
            [
                'data' => [
                    'system' => true,
                    'country' => null,
                    'region' => null,
                    'postalCode' => null,
                    'city' => null,
                    'street' => null,
                ],
                'setSystemConfig' => true,
                'isSystem' => true
            ],
            [
                'data' => [
                    'system' => true,
                    'country' => null,
                    'region' => null,
                    'postalCode' => null,
                    'city' => null,
                    'street' => null,
                ],
                'setSystemConfig' => false,
                'isSystem' => true
            ]
        ];
    }

    /**
     * @param Warehouse $warehouse
     * @param bool $setSystemConfig
     * @param bool $isSystem
     */
    protected function assertShippingOrigin(Warehouse $warehouse, $setSystemConfig, $isSystem)
    {
        $this->setSystemConfig(!$setSystemConfig);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_warehouse_view', ['id' => $warehouse->getId()]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        $translator = $this->getContainer()->get('translator');
        $systemConfigLabel = $translator->trans('orob2b.shipping.warehouse.system_configuration.label');

        if ($isSystem && $setSystemConfig) {
            $this->assertContains($systemConfigLabel, $html);
        } else {
            $this->assertNotContains($systemConfigLabel, $html);
        }

        if ($isSystem && !$setSystemConfig) {
            $this->assertNotContains($translator->trans('orob2b.shipping.warehouse.section.shipping_origin'), $html);
        } else {
            $shippingOriginWarehouse = $this->getShippingOriginWarehouse($warehouse);

            $this->assertContains($shippingOriginWarehouse->getCountry()->getIso2Code(), $html);
            $this->assertContains($shippingOriginWarehouse->getRegion()->getCode(), $html);
            $this->assertContains(strtoupper($shippingOriginWarehouse->getCity()), $html);
            $this->assertContains($shippingOriginWarehouse->getPostalCode(), $html);
            $this->assertContains($shippingOriginWarehouse->getStreet(), $html);
        }
    }

    /**
     * @param Warehouse $warehouse
     * @return null|ShippingOriginWarehouse
     */
    protected function getShippingOriginWarehouse(Warehouse $warehouse)
    {
        return $this->getContainer()
            ->get('orob2b_shipping.shipping_origin.provider')
            ->getShippingOriginByWarehouse($warehouse);
    }

    /**
     * @param bool $reset
     */
    protected function setSystemConfig($reset = false)
    {
        $configManager = $this->getContainer()->get('oro_config.global');

        if (!$reset) {
            $configManager->set(
                'orob2b_shipping.shipping_origin',
                [
                    'country' => 'US',
                    'region' => 'US-LA',
                    'region_text' => null,
                    'postalCode' => 'syszipcode',
                    'city' => 'syscity',
                    'street' => 'sysstreet',
                    'street2' => 'sysstreet2',
                ]
            );
        } else {
            $configManager->reset('orob2b_shipping.shipping_origin');
        }

        $configManager->flush();
    }
}
