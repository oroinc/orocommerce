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
    protected $uPSShippingMethod;

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
        $this->uPSShippingMethod = new UPSShippingMethod($this->transportProvider, $this->channel, $this->registry);
    }

    public function testIsGrouped()
    {
        $this->assertTrue($this->uPSShippingMethod->isGrouped());
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('ups_1', $this->uPSShippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        $this->assertEquals('ups_channel_1', $this->uPSShippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->uPSShippingMethod->getTypes();

        $this->assertCount(1, $types);
        $this->assertEquals('ups_identifier', $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = 'ups_identifier';
        $type = $this->uPSShippingMethod->getType($identifier);

        $this->assertInstanceOf(UPSShippingMethodType::class, $type);
        $this->assertEquals('ups_identifier', $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->uPSShippingMethod->getOptionsConfigurationFormType();

        $this->assertEquals(UPSShippingMethodOptionsType::class, $type);
    }

    public function testGetSortOrder()
    {
        $this->assertEquals('20', $this->uPSShippingMethod->getSortOrder());
    }

    public function testCalculatePrices()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = [];
        $optionsByTypes = [];

        $uPSShippingMethodType = $this->getMockBuilder(UPSShippingMethodType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uPSShippingMethodType->expects(self::any())->method('getIdentifier')->willReturn('type_1');
        $uPSShippingMethodType->expects(self::any())->method('calculatePrice')->willReturn(Price::create(20, 'USD'));

        /** @var UPSShippingMethod|\PHPUnit_Framework_MockObject_MockObject $uPSShippingMethod */
        $uPSShippingMethod = $this->getMockBuilder(UPSShippingMethod::class)->setMethods(['getTypes'])
            ->disableOriginalConstructor()
            ->getMock();
        $uPSShippingMethod->expects(self::any())->method('getTypes')->willReturn([$uPSShippingMethodType]);

        $prices = $uPSShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        $this->assertCount(1, $prices);
        $this->assertTrue(array_key_exists('type_1', $prices));
        $this->assertEquals(Price::create(20, 'USD'), $prices['type_1']);
    }
}
