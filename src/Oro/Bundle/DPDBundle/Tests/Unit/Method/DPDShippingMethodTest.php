<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DPDShippingMethodTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @internal
     */
    const IDENTIFIER = 'dpd_1';

    /**
     * @internal
     */
    const LABEL = 'dpd_label';

    /**
     * @internal
     */
    const TYPE_IDENTIFIER = '59';

    /**
     * @var DPDTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var DPDRequestFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dpdRequestFactory;

    /**
     * @var DPDShippingMethod
     */
    protected $dpdShippingMethod;

    protected function setUp()
    {
        $this->transportProvider = $this->createMock(DPDTransportProvider::class);

        $shippingService = $this->getEntity(
            ShippingService::class,
            ['code' => 'dpd_identifier', 'description' => 'dpd_label']
        );

        /* @var DPDRequestFactory | \PHPUnit_Framework_MockObject_MockObject $priceRequestFactory */
        $this->dpdRequestFactory = $this->createMock(DPDRequestFactory::class);

        $this->transport = $this->createMock(DPDTransport::class);
        $this->transport->expects(self::any())->method('getApplicableShippingServices')->willReturn([$shippingService]);

        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);
        $type
            ->method('calculatePrice')
            ->willReturn(Price::create('1.0', 'USD'));

        $handler = $this->createMock(DPDHandlerInterface::class);
        $handler
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);

        $this->dpdShippingMethod =
            new DPDShippingMethod(
                self::IDENTIFIER,
                self::LABEL,
                [$type],
                [$handler],
                $this->transport,
                $this->transportProvider
            );
    }

    public function testIsGrouped()
    {
        static::assertTrue($this->dpdShippingMethod->isGrouped());
    }

    public function testGetIdentifier()
    {
        static::assertEquals(self::IDENTIFIER, $this->dpdShippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        static::assertEquals(self::LABEL, $this->dpdShippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->dpdShippingMethod->getTypes();

        static::assertCount(1, $types);
        static::assertEquals(self::TYPE_IDENTIFIER, $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = self::TYPE_IDENTIFIER;
        $type = $this->dpdShippingMethod->getType($identifier);

        static::assertInstanceOf(ShippingMethodTypeInterface::class, $type);
        static::assertEquals(self::TYPE_IDENTIFIER, $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->dpdShippingMethod->getOptionsConfigurationFormType();

        static::assertEquals(DPDShippingMethodOptionsType::class, $type);
    }

    public function testGetSortOrder()
    {
        static::assertEquals('20', $this->dpdShippingMethod->getSortOrder());
    }

    public function testCalculatePrices()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['handling_fee' => null];
        $optionsByTypes = [self::TYPE_IDENTIFIER => ['handling_fee' => null]];

        $prices = $this->dpdShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        static::assertCount(1, $prices);
        static::assertTrue(array_key_exists(self::TYPE_IDENTIFIER, $prices));
        static::assertEquals(Price::create('1.0', 'USD'), $prices[self::TYPE_IDENTIFIER]);
    }

    /**
     * @param string      $number
     * @param string|null $resultURL
     *
     * @dataProvider trackingDataProvider
     */
    public function testGetTrackingLink($number, $resultURL)
    {
        static::assertEquals($resultURL, $this->dpdShippingMethod->getTrackingLink($number));
    }

    /**
     * @return array
     */
    public function trackingDataProvider()
    {
        return [
            'emptyTrackingNumber' => [
                'number' => '',
                'resultURL' => null,
            ],
            'wrongTrackingNumber2' => [
                'number' => '123123123123',
                'resultURL' => null,
            ],
            'rightTrackingNumber' => [
                'number' => '09980525414724',
                'resultURL' => DPDShippingMethod::TRACKING_URL.'09980525414724',
            ],
        ];
    }
}
