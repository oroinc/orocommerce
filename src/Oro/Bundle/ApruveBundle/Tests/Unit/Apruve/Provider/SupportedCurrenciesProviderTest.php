<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Helper;

use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProvider;

class SupportedCurrenciesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SupportedCurrenciesProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->provider = new SupportedCurrenciesProvider();
    }

    public function testGetCurrencies()
    {
        $actual = $this->provider->getCurrencies();
        $this->assertSame(['USD'], $actual);
    }

    /**
     * @dataProvider currenciesDataProvider
     *
     * @param string $currency
     * @param string $expected
     */
    public function testIsSupported($currency, $expected)
    {
        $actual = $this->provider->isSupported($currency);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function currenciesDataProvider()
    {
        return [
            ['USD', true],
            ['EUR', false],
        ];
    }
}
