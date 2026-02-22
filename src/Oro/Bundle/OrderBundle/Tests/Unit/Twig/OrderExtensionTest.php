<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Oro\Bundle\OrderBundle\Twig\OrderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class OrderExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private Environment&MockObject $environment;
    private SourceDocumentFormatter&MockObject $sourceDocumentFormatter;
    private ShippingTrackingFormatter&MockObject $shippingTrackingFormatter;
    private OrderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->sourceDocumentFormatter = $this->createMock(SourceDocumentFormatter::class);
        $this->shippingTrackingFormatter = $this->createMock(ShippingTrackingFormatter::class);

        $container = self::getContainerBuilder()
            ->add(SourceDocumentFormatter::class, $this->sourceDocumentFormatter)
            ->add(ShippingTrackingFormatter::class, $this->shippingTrackingFormatter)
            ->getContainer($this);

        $this->extension = new OrderExtension($container);
    }

    private function prepareOrder(): Order
    {
        $order = new Order();
        $shippingTracking = new OrderShippingTracking();
        $shippingTracking->setMethod('shipping Method Name');
        $shippingTracking->setNumber('7s45');
        $order->addShippingTracking($shippingTracking);

        return $order;
    }

    public function testGetTemplateContent()
    {
        $context = ['parameter' => 'value'];
        $content = 'html content';
        $template = $this->createMock(Template::class);
        $template->expects(self::once())
            ->method('render')
            ->with($context)
            ->willReturn($content);

        $templateName = 'some:name';
        $this->environment->expects(self::once())
            ->method('resolveTemplate')
            ->with($templateName)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        self::assertEquals(
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

        $this->sourceDocumentFormatter->expects(self::once())
            ->method('format')
            ->with($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
            ->willReturn($formattedData);

        self::assertEquals(
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

        $this->shippingTrackingFormatter->expects(self::once())
            ->method('formatShippingTrackingMethod')
            ->with($shippingMethodName)
            ->willReturn($formattedMethod);

        self::assertEquals(
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

        $this->shippingTrackingFormatter->expects(self::once())
            ->method('formatShippingTrackingLink')
            ->with($shippingMethodName, $trackingNumber)
            ->willReturn($formattedLink);

        self::assertEquals(
            $formattedLink,
            self::callTwigFunction(
                $this->extension,
                'oro_order_format_shipping_tracking_link',
                [$shippingMethodName, $trackingNumber]
            )
        );
    }

    public function testGetShippingTrackings()
    {
        $order = $this->prepareOrder();
        $formattedLink = 'shipping link';

        $this->shippingTrackingFormatter->expects(self::once())
            ->method('formatShippingTrackingMethod')
            ->with('shipping Method Name')
            ->willReturn('shipping Method Name');

        $this->shippingTrackingFormatter->expects(self::once())
            ->method('formatShippingTrackingLink')
            ->with('shipping Method Name', '7s45')
            ->willReturn($formattedLink);

        self::assertEquals(
            [
                [
                    'method' => 'shipping Method Name',
                    'number' => '7s45',
                    'link'   => 'shipping link'
                ]
            ],
            self::callTwigFunction(
                $this->extension,
                'oro_order_get_shipping_trackings',
                [$order]
            )
        );
    }
}
