<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OrderExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;

    /**
     * @var OrderExtension
     */
    private $orderExtension;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->orderExtension = new OrderExtension($this->container);
    }

    public function testGetFilters()
    {
        $filters = $this->orderExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
    }

    public function testGetFunctions()
    {
        $filters = $this->orderExtension->getFunctions();

        $this->assertCount(3, $filters);
        $this->assertInstanceOf(TwigFunction::class, $filters[0]);
        $this->assertInstanceOf(TwigFunction::class, $filters[1]);
        $this->assertInstanceOf(TwigFunction::class, $filters[2]);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_order_order', $this->orderExtension->getName());
    }

    public function testGetTemplateContent()
    {
        /** @var Environment|\PHPUnit\Framework\MockObject\MockObject $environment **/
        $environment = $this->createMock(Environment::class);

        $context = ['parameter' => 'value'];
        $content = 'html conten';
        /** @var Template|\PHPUnit\Framework\MockObject\MockObject $template */
        $template = $this->createMock(Template::class);
        $template
            ->expects($this->once())
            ->method('render')
            ->with($context)
            ->willReturn($content);

        $templateName = 'some:name';
        $environment
            ->expects($this->once())
            ->method('resolveTemplate')
            ->with($templateName)
            ->willReturn($template);

        $this->assertEquals($content, $this->orderExtension->getTemplateContent($environment, $templateName, $context));
    }

    public function testFormatSourceDocument()
    {
        $sourceEntityClass = 'entityClas';
        $sourceEntityId = 77;
        $sourceEntityIdentifier = 'id';
        $formattedData = 'html data';

        /** @var SourceDocumentFormatter|\PHPUnit\Framework\MockObject\MockObject $sourceDocumentFormatter */
        $sourceDocumentFormatter = $this->createMock(SourceDocumentFormatter::class);
        $sourceDocumentFormatter
            ->expects($this->once())
            ->method('format')
            ->with($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
            ->willReturn($formattedData);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('oro_order.formatter.source_document')
            ->willReturn($sourceDocumentFormatter);

        $this->assertEquals(
            $formattedData,
            $this->orderExtension->formatSourceDocument($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
        );
    }

    public function testFormatShippingTrackingMethod()
    {
        $shippingMethodName = 'shippingMethod';
        $formattedMethod = 'shipping method';

        /** @var ShippingTrackingFormatter|\PHPUnit\Framework\MockObject\MockObject $shippingTrackingFormatter */
        $shippingTrackingFormatter = $this->createMock(ShippingTrackingFormatter::class);
        $shippingTrackingFormatter
            ->expects($this->once())
            ->method('formatShippingTrackingMethod')
            ->with($shippingMethodName)
            ->willReturn($formattedMethod);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('oro_order.formatter.shipping_tracking')
            ->willReturn($shippingTrackingFormatter);

        $this->assertEquals(
            $formattedMethod,
            $this->orderExtension->formatShippingTrackingMethod($shippingMethodName)
        );
    }

    public function testFormatShippingTrackingLink()
    {
        $shippingMethodName = 'shippingMethod';
        $trackingNumber = '7s45';
        $formattedLink = 'shipping link';

        /** @var ShippingTrackingFormatter|\PHPUnit\Framework\MockObject\MockObject $shippingTrackingFormatter */
        $shippingTrackingFormatter = $this->createMock(ShippingTrackingFormatter::class);
        $shippingTrackingFormatter
            ->expects($this->once())
            ->method('formatShippingTrackingLink')
            ->with($shippingMethodName, $trackingNumber)
            ->willReturn($formattedLink);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('oro_order.formatter.shipping_tracking')
            ->willReturn($shippingTrackingFormatter);

        $this->assertEquals(
            $formattedLink,
            $this->orderExtension->formatShippingTrackingLink($shippingMethodName, $trackingNumber)
        );
    }
}
