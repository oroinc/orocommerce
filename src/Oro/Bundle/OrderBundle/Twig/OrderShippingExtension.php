<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;

class OrderShippingExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction(
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
