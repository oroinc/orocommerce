<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to display the name of a shipping method:
 *   - oro_order_shipping_method_label
 */
class OrderShippingExtension extends AbstractExtension
{
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
     * @return ShippingMethodLabelTranslator|null
     */
    protected function getLabelTranslator()
    {
        return $this->container->get(
            'oro_shipping.translator.shipping_method_label',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
    }

    /**
     * @return array
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
}
