<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;

/**
 * @dbIsolation
 */
class SystemConfigurationTest extends WebTestCase
{
    /** @var ConfigManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->configManager = $this->getContainer()->get('oro_config.global');
    }

    protected function tearDown()
    {
        $this->configManager->reset('oro_tax.tax_enable');
        $this->configManager->reset('oro_tax.tax_provider');
        $this->configManager->reset('oro_tax.origin_address');
        $this->configManager->flush();

        parent::tearDown();
    }

    public function testConfig()
    {
        $this->assertTrue($this->configManager->get('oro_tax.tax_enable'));
        $this->assertEquals(BuiltInTaxProvider::NAME, $this->configManager->get('oro_tax.tax_provider'));

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'tax_calculation']
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $token = $this->getContainer()->get('security.csrf.token_manager')->getToken('tax_calculation')->getValue();
        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'tax_calculation' => [
                    'oro_tax___tax_enable' => [
                        'use_parent_scope_value' => false,
                        'value' => false,
                    ],
                    'oro_tax___tax_provider' => [
                        'use_parent_scope_value' => false,
                        'value' => BuiltInTaxProvider::NAME,
                    ],
                    'oro_tax___origin_address' => [
                        'use_parent_scope_value' => false,
                        'value' => ['country' => 'US', 'region' => 'US-NY', 'postal_code' => '00501'],
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->configManager->reload();
        $this->assertFalse($this->configManager->get('oro_tax.tax_enable'));
        $this->assertEquals(BuiltInTaxProvider::NAME, $this->configManager->get('oro_tax.tax_provider'));
        $this->assertEquals(
            [
                'country' => 'US',
                'region' => 'US-NY',
                'region_text' => null,
                'postal_code' => '00501',
            ],
            $this->configManager->get('oro_tax.origin_address')
        );
    }

    public function testBuiltInProvider()
    {
        $providers = $this->getContainer()->get('oro_tax.provider.tax_provider_registry')->getProviders();

        $provider = reset($providers);

        $this->assertNotNull($provider);
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider', $provider);
    }
}
