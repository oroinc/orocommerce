<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\PhpUtils\ArrayUtil;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class SystemConfigurationTest extends WebTestCase
{
    const WAREHOUSE_CLASS = 'OroB2B\Bundle\WarehouseBundle\Entity\Warehouse';

    /** @var ConfigManager */
    protected $configManager;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    protected function tearDown()
    {
        $this->configManager->reset('orob2b_shipping.shipping_origin');
        $this->configManager->flush();

        parent::tearDown();
    }

    public function testConfig()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'shipping_origin']
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $token = $this->getContainer()->get('security.csrf.token_manager')->getToken('shipping_origin')->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'shipping_origin' => [
                    'orob2b_shipping___shipping_origin' => [
                        'use_parent_scope_value' => false,
                        'value' => [
                            'country' => 'US',
                            'region' => 'US-NY',
                            'postalCode' => 'code2',
                            'city' => 'city2',
                            'street' => 'street2',
                            'street2' => 'street3',
                        ],
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->configManager->reload();

        $this->assertEquals(
            [
                'country' => 'US',
                'region' => 'US-NY',
                'region_text' => null,
                'postalCode' => 'code2',
                'city' => 'city2',
                'street' => 'street2',
                'street2' => 'street3',
            ],
            $this->configManager->get('orob2b_shipping.shipping_origin')
        );
    }

    public function testWarehouse()
    {
        /** @var EntityRepository $repo */
        $repo = $this->doctrineHelper
            ->getEntityManagerForClass(static::WAREHOUSE_CLASS)
            ->getRepository(static::WAREHOUSE_CLASS);

        /** @var Warehouse $warehouse */
        $warehouse = $repo->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_warehouse_update',
                ['id' => $warehouse->getId()]
            )
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('shipping_origin')
            ->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'shipping_origin_warehouse' => [
                    'country' => 'US',
                    'region' => 'US-NY',
                    'postalCode' => 'code2',
                    'city' => 'city2',
                    'street' => 'street2',
                    'street2' => 'street3',
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('shipping_origin')
            ->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        // Retake all data from regenerated form and merge with expected
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'shipping_origin_warehouse' => [
                    'country' => 'US',
                    'region' => 'US-NY',
                    'postalCode' => 'code2',
                    'city' => 'city2',
                    'street' => 'street2',
                    'street2' => 'street3',
                    '_token' => $token,
                ],
            ]
        );

        $this->assertEquals(
            $formData,
            $form->getPhpValues()
        );
    }
}
