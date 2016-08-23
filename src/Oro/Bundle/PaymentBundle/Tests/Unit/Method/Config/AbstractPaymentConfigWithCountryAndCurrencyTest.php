<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\DependencyInjection\Configuration;
use Oro\Bundle\PaymentBundle\Method\Config\CountryConfigAwareInterface;
use Oro\Bundle\PaymentBundle\Method\Config\CurrencyConfigAwareInterface;

abstract class AbstractPaymentConfigWithCountryAndCurrencyTest extends AbstractPaymentConfigTestCase
{
    /** @var CountryConfigAwareInterface|CurrencyConfigAwareInterface */
    protected $config;

    public function testAllCountriesAllowed()
    {
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_countries',
            Configuration::ALLOWED_COUNTRIES_ALL
        );
        $result = $this->config->isCountryApplicable([]);
        $this->assertTrue($result);
    }

    public function testEmptyContext()
    {
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_countries',
            false
        );
        $result = $this->config->isCountryApplicable([]);
        $this->assertFalse($result);
    }

    public function testAllowedCountriesEmpty()
    {
        $context = ['country' => 'US'];
        $this->setConfig(
            $this->at(0),
            $this->getConfigPrefix() . 'allowed_countries',
            false
        );
        $this->setConfig(
            $this->at(1),
            $this->getConfigPrefix() . 'selected_countries',
            []
        );
        $result = $this->config->isCountryApplicable($context);
        $this->assertFalse($result);
    }

    public function testAllowedCountriesNotEquals()
    {
        $context = ['country' => 'US'];
        $this->setConfig(
            $this->at(0),
            $this->getConfigPrefix() . 'allowed_countries',
            false
        );
        $this->setConfig(
            $this->at(1),
            $this->getConfigPrefix() . 'selected_countries',
            ['UK']
        );
        $this->setConfig(
            $this->at(2),
            $this->getConfigPrefix() . 'selected_countries',
            ['UK']
        );
        $result = $this->config->isCountryApplicable($context);
        $this->assertFalse($result);
    }

    public function testOneAllowedCountryEquals()
    {
        $context = ['country' => 'US'];
        $this->setConfig(
            $this->at(0),
            $this->getConfigPrefix() . 'allowed_countries',
            false
        );
        $this->setConfig(
            $this->at(1),
            $this->getConfigPrefix() . 'selected_countries',
            ['UK', 'US']
        );
        $this->setConfig(
            $this->at(2),
            $this->getConfigPrefix() . 'selected_countries',
            ['UK', 'US']
        );
        $result = $this->config->isCountryApplicable($context);
        $this->assertTrue($result);
    }

    public function testEmptyCurrencyContext()
    {
        $context = [];
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_currencies',
            ['usd']
        );
        $result = $this->config->isCurrencyApplicable($context);
        $this->assertFalse($result);
    }

    public function testNoAllowedCurrencies()
    {
        $context = ['currency' => 'eur'];
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_currencies',
            []
        );
        $result = $this->config->isCurrencyApplicable($context);
        $this->assertFalse($result);
    }

    public function testCurrenciesNotEqual()
    {
        $context = ['currency' => 'eur'];
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_currencies',
            ['usd']
        );
        $result = $this->config->isCurrencyApplicable($context);
        $this->assertFalse($result);
    }

    public function testCurrencyEqual()
    {
        $context = ['currency' => 'eur'];
        $this->setConfig(
            $this->once(),
            $this->getConfigPrefix() . 'allowed_currencies',
            ['usd', 'eur']
        );
        $result = $this->config->isCurrencyApplicable($context);
        $this->assertTrue($result);
    }
}
