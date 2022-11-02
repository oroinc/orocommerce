# Freight Classes Extension

A developer can expand "Freight Classes" within the application by following the next steps:

 * add migration to the bundle, as described [here](./Resources/doc/provide-units.md);
 * add the 'Extension' class, [example](#example);
 * register the extension in the "services.yml" file.

All "Freight Classes Extensions" must implement the 'FreightClassesExtensionInterface'.
The "isApplicable" method is used to determine if "FreightClass" can be handled by the extension for provided shipping options.

#### Example:

```php
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
    
    //Other protected methods that implement logic to verify given options against given freight class
}
```


The extension must be registered in the "services.yml" file with the "*oro_shipping.extension.freight_classes*" tag that enables the main service to collect all extensions.

```yml
services:
    oro_shipping_demo.extension.shipping_freight_classes:
        class: Oro\Bundle\ShippingDemoBundle\Extension\Shipping\FreightClassesExtension
        tags:
            - { name: oro_shipping.extension.freight_classes }

```
