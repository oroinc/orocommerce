<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\PhpUtils\ArrayUtil;

class SystemConfigurationTest extends WebTestCase
{
    /** @var ConfigManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->configManager = $this->getContainer()->get('oro_config.manager');
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
}
