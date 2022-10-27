<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginConfigSearchProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingOriginConfigSearchProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ShippingOriginConfigSearchProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ShippingOriginConfigSearchProvider(
            $this->translator,
            $this->configManager
        );
    }

    /**
     * @dataProvider supportsDataProvider
     * @param string $name
     * @param bool $expected
     */
    public function testSupports($name, $expected)
    {
        $this->assertSame($expected, $this->provider->supports($name));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported' => ['oro_shipping.shipping_origin', true],
            'not supported' => ['oro_test.config', false]
        ];
    }

    public function testGetData()
    {
        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return $str . ' TRANS';
            });

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shipping.shipping_origin')
            ->willReturn([
                'region_text' => 'Alabama',
                'postalCode' => '35004',
                'country' => 'US',
                'region' => 'US-AL',
                'city' => 'City Name',
                'street' => 'Street Name',
                'street2' => 'Street2 Name',
            ]);

        $this->assertEquals(
            [
                'oro.shipping.shipping_origin.country.label TRANS',
                'oro.shipping.shipping_origin.region.label TRANS',
                'oro.shipping.shipping_origin.postal_code.label TRANS',
                'oro.shipping.shipping_origin.city.label TRANS',
                'oro.shipping.shipping_origin.street.label TRANS',
                'oro.shipping.shipping_origin.street2.label TRANS',
                'oro.shipping.shipping_origin.region_text.label TRANS',
                'Alabama',
                '35004',
                'US',
                'US-AL',
                'City Name',
                'Street Name',
                'Street2 Name'
            ],
            $this->provider->getData('oro_shipping.shipping_origin')
        );
    }
}
