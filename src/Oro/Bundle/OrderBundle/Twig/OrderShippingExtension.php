<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;

class OrderShippingExtension extends \Twig_Extension
{
    /** @var ShippingMethodLabelTranslator|null */
    private $labelTranslator;

    /**
     * @param ShippingMethodLabelTranslator $labelTranslator
     */
    public function setShippingLabelFormatter(ShippingMethodLabelTranslator $labelTranslator)
    {
        $this->labelTranslator = $labelTranslator;
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
        if (!$this->labelTranslator) {
            return $shippingMethodName . ', ' . $shippingTypeName;
        }

        return $this->labelTranslator->getShippingMethodWithTypeLabel(
            $shippingMethodName,
            $shippingTypeName
        );
    }
}
