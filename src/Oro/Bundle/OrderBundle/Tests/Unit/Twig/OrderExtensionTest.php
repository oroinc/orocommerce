<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class OrderExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var SourceDocumentFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $sourceDocumentFormatter;

    /** @var ShippingTrackingFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingTrackingFormatter;

    /** @var OrderExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->sourceDocumentFormatter = $this->createMock(SourceDocumentFormatter::class);
        $this->shippingTrackingFormatter = $this->createMock(ShippingTrackingFormatter::class);

        $container = self::getContainerBuilder()
            ->add('oro_order.formatter.source_document', $this->sourceDocumentFormatter)
            ->add('oro_order.formatter.shipping_tracking', $this->shippingTrackingFormatter)
            ->getContainer($this);

        $this->extension = new OrderExtension($container);
    }

    public function testGetTemplateContent()
    {
        $context = ['parameter' => 'value'];
        $content = 'html conten';
        $template = $this->createMock(Template::class);
        $template->expects($this->once())
            ->method('render')
            ->with($context)
            ->willReturn($content);

        $templateName = 'some:name';
        $this->environment->expects($this->once())
            ->method('resolveTemplate')
            ->with($templateName)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->assertEquals(
            $content,
            self::callTwigFunction(
                $this->extension,
                'oro_order_get_template_content',
                [$this->environment, $templateName, $context]
            )
        );
    }

    public function testFormatSourceDocument()
    {
        $sourceEntityClass = 'entityClas';
        $sourceEntityId = 77;
        $sourceEntityIdentifier = 'id';
        $formattedData = 'html data';

        $this->sourceDocumentFormatter->expects($this->once())
            ->method('format')
            ->with($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
            ->willReturn($formattedData);

        $this->assertEquals(
            $formattedData,
            self::callTwigFilter(
                $this->extension,
                'oro_order_format_source_document',
                [$sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier]
            )
        );
    }

    public function testFormatShippingTrackingMethod()
    {
        $shippingMethodName = 'shippingMethod';
        $formattedMethod = 'shipping method';

        $this->shippingTrackingFormatter->expects($this->once())
            ->method('formatShippingTrackingMethod')
            ->with($shippingMethodName)
            ->willReturn($formattedMethod);

        $this->assertEquals(
            $formattedMethod,
            self::callTwigFunction(
                $this->extension,
                'oro_order_format_shipping_tracking_method',
                [$shippingMethodName]
            )
        );
    }

    public function testFormatShippingTrackingLink()
    {
        $shippingMethodName = 'shippingMethod';
        $trackingNumber = '7s45';
        $formattedLink = 'shipping link';

        $this->shippingTrackingFormatter->expects($this->once())
            ->method('formatShippingTrackingLink')
            ->with($shippingMethodName, $trackingNumber)
            ->willReturn($formattedLink);

        $this->assertEquals(
            $formattedLink,
            self::callTwigFunction(
                $this->extension,
                'oro_order_format_shipping_tracking_link',
                [$shippingMethodName, $trackingNumber]
            )
        );
    }
}
