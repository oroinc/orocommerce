<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Url\Provider\Basic;

use Oro\Bundle\ApruveBundle\Client\Url\Provider\Basic\BasicApruveClientUrlProvider;

class BasicApruveClientUrlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicApruveClientUrlProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->provider = new BasicApruveClientUrlProvider();
    }

    public function testGetTestModeUrl()
    {
        static::assertEquals('https://test.apruve.com/api/v4/', $this->provider->getApruveUrl(true));
    }

    public function testGetProdModeUrl()
    {
        static::assertEquals('https://app.apruve.com/api/v4/', $this->provider->getApruveUrl(false));
    }
}
