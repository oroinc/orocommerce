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
        $this->extension = new OrderExtension($this->sourceDocumentFormatter, $this->shippingTrackingFormatter);
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
                [$this->sourceDocumentFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
        ];
        static::assertEquals($expected, $this->extension->getFilters());
    }

    public function testGetFunctions()
    {
        $expected = [
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_method',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingMethod'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_link',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingLink'],
                ['is_safe' => ['html']]
            )
        ];
        static::assertEquals($expected, $this->extension->getFunctions());
    }
}
