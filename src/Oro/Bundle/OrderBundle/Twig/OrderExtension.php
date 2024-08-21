<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to format shipping tracking information and a function to render another template's content:
 *   - oro_order_format_shipping_tracking_method
 *   - oro_order_format_shipping_tracking_link
 *   - oro_order_get_template_content
 *   - oro_order_get_shipping_trackings
 *
 * Provides a Twig filter to display source document name:
 *   - oro_order_format_source_document
 */
class OrderExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return SourceDocumentFormatter
     */
    protected function getSourceDocumentFormatter()
    {
        return $this->container->get('oro_order.formatter.source_document');
    }

    /**
     * @return ShippingTrackingFormatter
     */
    protected function getShippingTrackingFormatter()
    {
        return $this->container->get('oro_order.formatter.shipping_tracking');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_order_format_source_document',
                [$this, 'formatSourceDocument']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_order_format_shipping_tracking_method',
                [$this, 'formatShippingTrackingMethod']
            ),
            new TwigFunction(
                'oro_order_format_shipping_tracking_link',
                [$this, 'formatShippingTrackingLink']
            ),
            new TwigFunction(
                'oro_order_get_template_content',
                [$this, 'getTemplateContent'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'oro_order_get_shipping_trackings',
                [$this, 'getShippingTrackings']
            ),
        ];
    }

    /**
     * @param Environment $environment
     * @param string $templateName
     * @param array $context
     * @return string
     */
    public function getTemplateContent(Environment $environment, $templateName, array $context)
    {
        $template = $environment->resolveTemplate($templateName);

        return $template->render($context);
    }

    /**
     * @param string $sourceEntityClass
     * @param integer $sourceEntityId
     * @param string $sourceEntityIdentifier
     *
     * @return string
     */
    public function formatSourceDocument($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
    {
        return $this->getSourceDocumentFormatter()
            ->format($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier);
    }

    /**
     * @param string $shippingMethodName
     *
     * @return string
     */
    public function formatShippingTrackingMethod($shippingMethodName)
    {
        return $this->getShippingTrackingFormatter()
            ->formatShippingTrackingMethod($shippingMethodName);
    }

    /**
     * @param string $shippingMethodName
     * @param string $trackingNumber
     *
     * @return string
     */
    public function formatShippingTrackingLink($shippingMethodName, $trackingNumber)
    {
        return $this->getShippingTrackingFormatter()
            ->formatShippingTrackingLink($shippingMethodName, $trackingNumber);
    }

    public function getShippingTrackings(Order $entity): iterable
    {
        $orderShippingTrackings = $entity->getShippingTrackings() ?? [];
        $shippingTrackings = [];
        foreach ($orderShippingTrackings as $shippingTracking) {
            $shippingTrackings[] = [
                'number' => $shippingTracking->getNumber(),
                'method' => $this->getShippingTrackingFormatter()
                    ->formatShippingTrackingMethod($shippingTracking->getMethod()),
                'link' => $this->getShippingTrackingFormatter()
                    ->formatShippingTrackingLink($shippingTracking->getMethod(), $shippingTracking->getNumber())
            ];
        }

        return $shippingTrackings;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_order.formatter.source_document' => SourceDocumentFormatter::class,
            'oro_order.formatter.shipping_tracking' => ShippingTrackingFormatter::class,
        ];
    }
}
