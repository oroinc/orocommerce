<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use PHPUnit\Framework\TestCase;

// @codingStandardsIgnoreEnd

class FedexShippingMethodTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';
    const ICON_PATH = 'path';
    const ENABLED = true;

    /**
     * @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rateServiceRequestSettingsFactory;

    /**
     * @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rateServiceClient;

    protected function setUp(): void
    {
        $this->rateServiceRequestSettingsFactory = $this->createMock(
            FedexRateServiceRequestSettingsFactoryInterface::class
        );
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByRateServiceSettingsFactoryInterface::class);
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
                FedexShippingMethod::TRACKING_URL.$number,
                $method->getTrackingLink($number)
            );
        }

        static::assertNull($method->getTrackingLink('000'));
    }

    public function testCalculatePrices()
    {
        $settings = new FedexIntegrationSettings();

        $rules = [
            $this->createShippingServiceRule(1),
            $this->createShippingServiceRule(2),
            $this->createShippingServiceRule(3),
        ];
        $settings
            ->addShippingService($this->createShippingService('test2', $rules[0]))
            ->addShippingService($this->createShippingService('test3', $rules[0]))
            ->addShippingService($this->createShippingService('test4', $rules[1]))
            ->addShippingService($this->createShippingService('test6', $rules[2]));

        $prices = [
            'test1' => Price::create(12.6, 'USD'),
            'test2' => Price::create(10.3, 'USD'),
            'test4' => Price::create(8.9, 'USD'),
            'test6' => Price::create(8.9, 'USD'),
        ];
        $requestSettings = [
            $this->createMock(FedexRateServiceRequestSettingsInterface::class),
            $this->createMock(FedexRateServiceRequestSettingsInterface::class),
        ];

        $requests = [
            new FedexRequest(),
            null,
        ];
        $response = new FedexRateServiceResponse('', 0, $prices);

        $method = $this->createShippingMethod($settings, []);
        $context = $this->createMock(ShippingContextInterface::class);

        $this->rateServiceRequestSettingsFactory
            ->expects(static::exactly(2))
            ->method('create')
            ->withConsecutive([$settings, $context, $rules[0]], [$settings, $context, $rules[1]])
            ->willReturnOnConsecutiveCalls($requestSettings[0], $requestSettings[1]);

        $this->rateServiceRequestFactory
            ->expects(static::exactly(2))
            ->method('create')
            ->withConsecutive([$requestSettings[0]], [$requestSettings[1]])
            ->willReturnOnConsecutiveCalls($requests[0], $requests[1]);

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->with($requests[0], $settings)
            ->willReturn($response);

        static::assertEquals(
            [
                'test2' => Price::create(13.8, 'USD'),
            ],
            $method->calculatePrices(
                $context,
                [FedexShippingMethod::OPTION_SURCHARGE => 1.5],
                [
                    'test1' => [FedexShippingMethod::OPTION_SURCHARGE => 1],
                    'test2' => [FedexShippingMethod::OPTION_SURCHARGE => 2],
                    'test3' => [FedexShippingMethod::OPTION_SURCHARGE => 3],
                    'test4' => [FedexShippingMethod::OPTION_SURCHARGE => 4],
                    'test5' => [FedexShippingMethod::OPTION_SURCHARGE => 5],
                ]
            )
        );
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ShippingMethodTypeInterface
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

    private function createShippingService(string $code, ShippingServiceRule $rule): FedexShippingService
    {
        $service = new FedexShippingService();
        $service
            ->setCode($code)
            ->setRule($rule);

        return $service;
    }

    /**
     * @param int $id
     *
     * @return ShippingServiceRule|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createShippingServiceRule(int $id)
    {
        $rule = $this->createMock(ShippingServiceRule::class);
        $rule
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $rule;
    }

    /**
     * @param FedexIntegrationSettings          $settings
     * @param ShippingMethodTypeInterface[]     $types
     *
     * @return FedexShippingMethod
     */
    private function createShippingMethod(
        FedexIntegrationSettings $settings,
        array $types
    ): FedexShippingMethod {
        return new FedexShippingMethod(
            $this->rateServiceRequestSettingsFactory,
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
