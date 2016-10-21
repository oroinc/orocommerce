<?php

namespace Oro\src\Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodTrackingLinkFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension;

class ShippingMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  ShippingMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var  ShippingMethodTrackingLinkFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodTrackingLinkFormatter;

    /**
     * @var ShippingMethodExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->shippingMethodLabelFormatter = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethodTrackingLinkFormatter = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Formatter\ShippingMethodTrackingLinkFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ShippingMethodExtension(
            $this->shippingMethodLabelFormatter,
            $this->shippingMethodTrackingLinkFormatter
        );
    }

    public function testGetFunctions()
    {
        static::assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_shipping_method_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_shipping_method_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_shipping_method_tracking_link',
                    [$this->shippingMethodTrackingLinkFormatter, 'formatShippingTrackingLink']
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        static::assertEquals(ShippingMethodExtension::SHIPPING_METHOD_EXTENSION_NAME, $this->extension->getName());
    }
}
