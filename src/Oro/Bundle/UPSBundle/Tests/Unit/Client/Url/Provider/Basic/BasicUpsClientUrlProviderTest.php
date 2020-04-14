<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Client\Url\Provider\Basic;

use Oro\Bundle\UPSBundle\Client\Url\Provider\Basic\BasicUpsClientUrlProvider;

class BasicUpsClientUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_URL = 'test_url';
    const PROD_URL = 'prod_url';

    /**
     * @var BasicUpsClientUrlProvider
     */
    private $testedUrlProvider;

    protected function setUp(): void
    {
        $this->testedUrlProvider = new BasicUpsClientUrlProvider(self::PROD_URL, self::TEST_URL);
    }

    public function testGetUpsUrlTestUrl()
    {
        $actualResult = $this->testedUrlProvider->getUpsUrl(true);

        $this->assertEquals($actualResult, self::TEST_URL);
    }

    public function testGetUpsUrlProdUrl()
    {
        $actualResult = $this->testedUrlProvider->getUpsUrl(false);

        $this->assertEquals($actualResult, self::PROD_URL);
    }
}
