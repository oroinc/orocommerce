<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to display the name of a shipping method:
 *   - oro_order_shipping_method_label
 */
class OrderShippingExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ShippingMethodLabelTranslator|null
     */
    protected function getLabelTranslator()
    {
        try {
            return $this->container->get('oro_shipping.translator.shipping_method_label');
        } catch (ServiceNotFoundException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_order_shipping_method_label',
                [$this, 'getShippingMethodLabel']
            ),
        ];
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     *
     * @return string
     */
    public function getShippingMethodLabel($shippingMethodName, $shippingTypeName)
    {
        $labelTranslator = $this->getLabelTranslator();
        if (null === $labelTranslator) {
            return $shippingMethodName . ', ' . $shippingTypeName;
        }

        return $labelTranslator->getShippingMethodWithTypeLabel(
            $shippingMethodName,
            $shippingTypeName
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shipping.translator.shipping_method_label' => ShippingMethodLabelTranslator::class,
        ];
    }
}
