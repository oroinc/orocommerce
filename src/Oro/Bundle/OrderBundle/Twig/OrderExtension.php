<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'oro_order_order';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
            new \Twig_SimpleFilter(
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
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_method',
                [$this, 'formatShippingTrackingMethod']
            ),
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_link',
                [$this, 'formatShippingTrackingLink']
            ),
            new \Twig_SimpleFunction(
                'oro_order_get_template_content',
                [$this, 'getTemplateContent'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param \Twig_Environment $environment
     * @param string $templateName
     * @param array $context
     * @return string
     */
    public function getTemplateContent(\Twig_Environment $environment, $templateName, array $context)
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
}
