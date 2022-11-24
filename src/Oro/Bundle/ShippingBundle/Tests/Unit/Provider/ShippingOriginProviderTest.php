<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingOriginProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ShippingOriginModelFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginModelFactory;

    /** @var ShippingOriginProvider */
    private $shippingOriginProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->shippingOriginModelFactory = new ShippingOriginModelFactory($this->doctrineHelper);

        $this->shippingOriginProvider = new ShippingOriginProvider(
            $this->configManager,
            $this->shippingOriginModelFactory
        );
    }

    /**
     * @dataProvider systemShippingOriginProvider
     */
    public function testGetSystemShippingOrigin(array $configData, Country $expectedCountry, Region $expectedRegion)
    {
        $country = new Country($configData['country']);
        $region = new Region($configData['region']);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityReference')
            ->willReturnMap([
                [Country::class, $configData['country'], $country],
                [Region::class, $configData['region'], $region]
            ]);

        $shippingOrigin = $this->shippingOriginModelFactory->create($configData);

        $this->assertEquals($expectedCountry, $shippingOrigin->getCountry());
        $this->assertEquals($expectedRegion, $shippingOrigin->getRegion());
    }

    public function systemShippingOriginProvider(): array
    {
        return [
            [
                'configData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
                'expectedCountry' => new Country('US'),
                'expectedRegion' => new Region('US-AL')
            ]
        ];
    }
}
