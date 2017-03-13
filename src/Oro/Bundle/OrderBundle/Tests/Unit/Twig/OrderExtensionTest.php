<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var SourceDocumentFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $sourceDocumentFormatter;

    /** @var ShippingTrackingFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingTrackingFormatter;

    /** @var OrderExtension */
    protected $extension;

    public function setUp()
    {
        $this->sourceDocumentFormatter = $this->getMockBuilder(SourceDocumentFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingTrackingFormatter = $this->getMockBuilder(ShippingTrackingFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_order.formatter.source_document', $this->sourceDocumentFormatter)
            ->add('oro_order.formatter.shipping_tracking', $this->shippingTrackingFormatter)
            ->getContainer($this);

        $this->extension = new OrderExtension($container);
    }

    public function testGetName()
    {
        static::assertEquals(OrderExtension::NAME, $this->extension->getName());
    }
}
