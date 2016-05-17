Freight Classes Extension
=========================

Create you own extension
```php
<?php

namespace OroB2B\Bundle\ShippingDemoBundle\Extension\Shipping;

use OroB2B\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClassInterface;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

class FreightClassesExtension implements FreightClassesExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options)
    {
        if ($class->getCode() === 'demo_class50') {
            return $this->checkClass50($options);
        } elseif ($class->getCode() === 'demo_class55') {
            return $this->checkClass55($options);
        }

        return false;
    }

    /**
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    protected function checkClass50(ProductShippingOptionsInterface $options)
    {
        $weight = $options->getWeight();

        return $weight && $weight->getUnit()->getCode() === 'lbs' && $weight->getValue() >= 50;
    }

    /**
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    protected function checkClass55(ProductShippingOptionsInterface $options)
    {
        $weight = $options->getWeight();

        return $weight && $weight->getUnit()->getCode() === 'lbs'
            && $weight->getValue() >= 35 && $weight->getValue() < 50;
    }
}
```

Register extension in services as Freight Classes Extension
```yml
services:
    orob2b_shipping_demo.extension.shipping_freight_classes:
        class: 'OroB2B\Bundle\ShippingDemoBundle\Extension\Shipping\FreightClassesExtension'
        tags:
            - { name: orob2b_shipping.extension.freight_classes }

```

All examples are available here [Demo Extension](https://github.com/laboro/dev/pull/386)
