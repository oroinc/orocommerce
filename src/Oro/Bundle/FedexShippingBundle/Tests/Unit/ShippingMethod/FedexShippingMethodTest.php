<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';
    const ICON_PATH = 'path';
    const ENABLED = true;

    /**
     * @var FedexRequestByContextAndSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceClient;

    protected function setUp()
    {
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByContextAndSettingsFactoryInterface::class);
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);
    }

    public function testGetters()
    {
        $types = [
            $this->createMethodType('test1'),
            $this->createMethodType('test2'),
        ];
        $method = $this->createShippingMethod(new FedexIntegrationSettings(), $types);

        static::assertTrue($method->isGrouped());
        static::assertSame(self::ENABLED, $method->isEnabled());
        static::assertSame(self::IDENTIFIER, $method->getIdentifier());
        static::assertSame(self::LABEL, $method->getLabel());
        static::assertSame(self::ICON_PATH, $method->getIcon());
        static::assertSame($types, $method->getTypes());
        static::assertSame(FedexShippingMethodOptionsType::class, $method->getOptionsConfigurationFormType());
        static::assertSame(20, $method->getSortOrder());

        static::assertNull($method->getType('no'));
        static::assertSame($types[0], $method->getType('test1'));
    }

    public function testGetTrackingLinkMatches()
    {
        $method = $this->createShippingMethod(new FedexIntegrationSettings(), []);
        $matchingNumbers = [
            '9612345676543456787654',
            '145678765432123',
            '345676543212',
            '98123454321 1111 2222',
            '9812354321 11112222',
            '98111111 2222',
            '9812345432111112222',
            '9822 11112222 333',
            '9822 11112222333',
            '475948574839472',
        ];

        foreach ($matchingNumbers as $number) {
            static::assertEquals(
                FedexShippingMethod::TRACKING_URL . $number,
                $method->getTrackingLink($number)
            );
        }

        static::assertNull($method->getTrackingLink('000'));
    }

    public function testCalculatePrices()
    {
        $settings = new FedexIntegrationSettings();
        $types = [
            $this->createMethodType('test1'),
            $this->createMethodType('test2'),
        ];
        $prices = [
            'test1' => Price::create(12.6, 'USD'),
            'test2' => Price::create(10.3, 'USD'),
        ];
        $method = $this->createShippingMethod($settings, $types);
        $context = $this->createMock(ShippingContextInterface::class);
        $request = new FedexRequest();
        $response = new FedexRateServiceResponse('', 0, $prices);

        $this->rateServiceRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($settings, $context)
            ->willReturn($request);

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        static::assertEquals(
            [
                'test1' => Price::create(15.1, 'USD'),
                'test2' => Price::create(14.8, 'USD'),
            ],
            $method->calculatePrices(
                $context,
                [FedexShippingMethod::OPTION_SURCHARGE => 1.5],
                [
                    'test1' => [FedexShippingMethod::OPTION_SURCHARGE => 1],
                    'test2' => [FedexShippingMethod::OPTION_SURCHARGE => 3],
                ]
            )
        );
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ShippingMethodTypeInterface
     */
    private function createMethodType(string $identifier)
    {
        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type
            ->expects(static::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $type;
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param array                    $types
     *
     * @return FedexShippingMethod
     */
    private function createShippingMethod(FedexIntegrationSettings $settings, array $types): FedexShippingMethod
    {
        return new FedexShippingMethod(
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            self::IDENTIFIER,
            self::LABEL,
            self::ICON_PATH,
            self::ENABLED,
            $settings,
            $types
        );
    }
}
