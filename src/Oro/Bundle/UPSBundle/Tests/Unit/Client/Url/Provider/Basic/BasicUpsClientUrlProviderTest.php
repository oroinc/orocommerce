<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Client\Url\Provider\Basic;

use Oro\Bundle\UPSBundle\Client\Url\Provider\Basic\BasicUpsClientUrlProvider;

class BasicUpsClientUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_URL = 'test_url';
    private const PROD_URL = 'prod_url';

    private BasicUpsClientUrlProvider $testedUrlProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->testedUrlProvider = new BasicUpsClientUrlProvider(self::PROD_URL, self::TEST_URL);
    }

    public function testGetUpsUrlTestUrl(): void
    {
        self::assertEquals(self::TEST_URL, $this->testedUrlProvider->getUpsUrl(true));
    }

    public function testGetUpsUrlProdUrl(): void
    {
        self::assertEquals(self::PROD_URL, $this->testedUrlProvider->getUpsUrl(false));
    }
}
