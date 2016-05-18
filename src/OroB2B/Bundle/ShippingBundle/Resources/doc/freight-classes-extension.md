# Freight Classes Extension #

To expand "Freight Classes" within application, developer must add to its own Bundle the following:

 * migration, as described [here](./Resources/doc/provide-units.md)
 * 'Extension' class, [example](#example)
 * register extension at "services.yml"

All "Freight Classes Extensions" must implement the 'FreightClassesExtensionInterface'.
Method "isApplicable" is used to determine if "FreightClass" can be handled by extension for given shipping options.

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


Extension must be registered at "services.yml" with tag "*orob2b_shipping.extension.freight_classes*", that allow main service to collect all extensions

```yml
services:
    orob2b_shipping_demo.extension.shipping_freight_classes:
        class: 'OroB2B\Bundle\ShippingDemoBundle\Extension\Shipping\FreightClassesExtension'
        tags:
            - { name: orob2b_shipping.extension.freight_classes }

```
