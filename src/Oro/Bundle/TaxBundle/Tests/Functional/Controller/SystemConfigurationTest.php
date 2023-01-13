<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ArrayUtil;

class SystemConfigurationTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->configManager = self::getConfigManager();
    }

    public function testConfig()
    {
        $this->assertTrue($this->configManager->get('oro_tax.tax_enable'));
        $this->assertEquals('built_in', $this->configManager->get('oro_tax.tax_provider'));

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'tax_calculation']
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $token = $this->getCsrfToken('tax_calculation')->getValue();
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
                        'value' => 'built_in',
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
        $this->assertFalse((bool)$this->configManager->get('oro_tax.tax_enable'));
        $this->assertEquals('built_in', $this->configManager->get('oro_tax.tax_provider'));
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
        $this->assertInstanceOf(BuiltInTaxProvider::class, $provider);
    }
}
