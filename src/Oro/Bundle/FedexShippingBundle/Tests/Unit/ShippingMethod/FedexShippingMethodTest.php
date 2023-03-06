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

// @codingStandardsIgnoreEnd

class FedexShippingMethodTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER = 'id';
    private const LABEL = 'label';
    private const ICON_PATH = 'path';
    private const ENABLED = true;

    /** @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestSettingsFactory;

    /** @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestFactory;

    /** @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject */
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

        self::assertTrue($method->isGrouped());
        self::assertSame(self::ENABLED, $method->isEnabled());
        self::assertSame(self::IDENTIFIER, $method->getIdentifier());
        self::assertSame(self::LABEL, $method->getLabel());
        self::assertSame(self::ICON_PATH, $method->getIcon());
        self::assertSame($types, $method->getTypes());
        self::assertSame(FedexShippingMethodOptionsType::class, $method->getOptionsConfigurationFormType());
        self::assertSame(20, $method->getSortOrder());

        self::assertNull($method->getType('no'));
        self::assertSame($types[0], $method->getType('test1'));
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
            self::assertEquals(
                'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=' . $number,
                $method->getTrackingLink($number)
            );
        }

        self::assertNull($method->getTrackingLink('000'));
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

        $this->rateServiceRequestSettingsFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$settings, $context, $rules[0]], [$settings, $context, $rules[1]])
            ->willReturnOnConsecutiveCalls($requestSettings[0], $requestSettings[1]);

        $this->rateServiceRequestFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$requestSettings[0]], [$requestSettings[1]])
            ->willReturnOnConsecutiveCalls($requests[0], $requests[1]);

        $this->rateServiceClient->expects(self::once())
            ->method('send')
            ->with($requests[0], $settings)
            ->willReturn($response);

        self::assertEquals(
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

    private function createMethodType(string $identifier): ShippingMethodTypeInterface
    {
        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::any())
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

    private function createShippingServiceRule(int $id): ShippingServiceRule
    {
        $rule = $this->createMock(ShippingServiceRule::class);
        $rule->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $rule;
    }

    private function createShippingMethod(FedexIntegrationSettings $settings, array $types): FedexShippingMethod
    {
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
