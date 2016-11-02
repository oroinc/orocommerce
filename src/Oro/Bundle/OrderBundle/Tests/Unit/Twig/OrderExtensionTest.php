<?php

namespace Oro\src\Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;

class OrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceDocumentFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceDocumentFormatter;

    /**
     * @var ShippingTrackingFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingTrackingFormatter;

    /**
     * @var ShippingMethodFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodFormatter;

    /**
     * @var OrderExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->sourceDocumentFormatter = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingTrackingFormatter = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethodFormatter = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new OrderExtension(
            $this->sourceDocumentFormatter,
            $this->shippingTrackingFormatter,
            $this->shippingMethodFormatter
        );
    }

    public function testGetName()
    {
        static::assertEquals(OrderExtension::NAME, $this->extension->getName());
    }

    public function testGetFilters()
    {
        $expected = [
            new \Twig_SimpleFilter(
                'oro_order_format_source_document',
                [$this->sourceDocumentFormatter, 'format']
            ),
        ];
        static::assertEquals($expected, $this->extension->getFilters());
    }

    public function testGetFunctions()
    {
        $expected = [
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_method',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingMethod']
            ),
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_link',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingLink']
            ),
            new \Twig_SimpleFunction(
                'oro_order_shipping_method_width_type_label',
                [$this->shippingMethodFormatter, 'formatShippingMethodWithTypeLabel']
            )
        ];
        static::assertEquals($expected, $this->extension->getFunctions());
    }
}
