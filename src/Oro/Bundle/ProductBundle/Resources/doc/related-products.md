Related Products
================

* [Owerview](#owerview)
* [Config provider](#config-provider)
* [Strategy](#strategy)

Overview
--------
Usually if you sell something, you also want to propose your customers
some related items. For example, if you are selling phones, it's good to
propose customer also some accessories to it. This is why we've created
Related Products functionality

Classes
-------

### Config provider
Class Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider\RelatedProductsRelatedItemConfigProvider
provides you configuration for Related Products.

##### isEnabled()
```php
$this->get('oro_product.related_item.related_product.config_provider')->isEnabled();
```
Returns information if related products functionality is enabled.


##### getLimit()
```php
$this->get('oro_product.related_item.related_product.config_provider')->getLimit();
```
Returns integer describing how many relation there can be for one product.

##### isBidirectional()
```php
$this->get('oro_product.related_item.related_product.config_provider')->isBidirectional();
```
Imagine that you have product A, and product B that is related to A.
If bidirectional is set to true, then product A will also be considered as related
to product B (see [Strategy](#strategy))

For more information about configuration see OroConfigBundle

### Strategy

The idea of this Strategy is to provide user related Products. You can see
very simple example in Oro\Bundle\ProductBundle\RelatedItem\Strategy\DatabaseStrategy
where related products are fetched from database. This strategy considers
all configuration from Config Provider.

Still, if you need to have some more complex logic you can create
your own strategy. Just implement Oro\Bundle\ProductBundle\RelatedItem\Strategy\StrategyInterface
and overload or decorate its definition: "oro_product.related_products.strategy".

To satisfy Strategy you need to provide one method:

```php
    /**
     * @param Product $product
     * @param array|null $context can be used to pass additional data
     * @return Product[]
     */
    public function findRelatedProducts(Product $product, array $context = []);
```
where $product is a Product object for which you are searching related products,
and $context is an array of any values, you need to provide, to make your
strategy work.
Function need to return array of the Products.


