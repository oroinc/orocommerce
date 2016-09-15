<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class UPSShippingMethodTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UPSTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $channel;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UPSShippingMethod
     */
    protected $upsShippingMethod;

    protected function setUp()
    {
        $this->transportProvider = $this->getMockBuilder(UPSTransportProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $shippingService = $this->getEntity(
            ShippingService::class,
            ['id' => 1, 'code' => 'ups_identifier', 'description' => 'ups_label', 'country' => new Country('US')]
        );

        $this->transport = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport->expects(self::any())->method('getApplicableShippingServices')->willReturn([$shippingService]);

        $this->channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'name' => 'ups_channel_1', 'transport' => $this->transport]
        );

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->upsShippingMethod = new UPSShippingMethod($this->transportProvider, $this->channel, $this->registry);
    }

    public function testIsGrouped()
    {
        static::assertTrue($this->upsShippingMethod->isGrouped());
    }

    public function testGetIdentifier()
    {
        static::assertEquals('ups_1', $this->upsShippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        static::assertEquals('ups_channel_1', $this->upsShippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->upsShippingMethod->getTypes();

        static::assertCount(1, $types);
        static::assertEquals('ups_identifier', $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = 'ups_identifier';
        $type = $this->upsShippingMethod->getType($identifier);

        static::assertInstanceOf(UPSShippingMethodType::class, $type);
        static::assertEquals('ups_identifier', $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->upsShippingMethod->getOptionsConfigurationFormType();

        static::assertEquals(UPSShippingMethodOptionsType::class, $type);
    }

    public function testGetSortOrder()
    {
        static::assertEquals('20', $this->upsShippingMethod->getSortOrder());
    }

    public function testCalculatePrices()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = [];
        $optionsByTypes = [];

        $upsShippingMethodType = $this->getMockBuilder(UPSShippingMethodType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $upsShippingMethodType->expects(self::any())->method('getIdentifier')->willReturn('type_1');
        $upsShippingMethodType->expects(self::any())->method('calculatePrice')->willReturn(Price::create(20, 'USD'));

        /** @var UPSShippingMethod|\PHPUnit_Framework_MockObject_MockObject $upsShippingMethod */
        $upsShippingMethod = $this->getMockBuilder(UPSShippingMethod::class)->setMethods(['getTypes'])
            ->disableOriginalConstructor()
            ->getMock();
        $upsShippingMethod->expects(self::any())->method('getTypes')->willReturn([$upsShippingMethodType]);

        $prices = $upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        static::assertCount(1, $prices);
        static::assertTrue(array_key_exists('type_1', $prices));
        static::assertEquals(Price::create(20, 'USD'), $prices['type_1']);
    }
}
