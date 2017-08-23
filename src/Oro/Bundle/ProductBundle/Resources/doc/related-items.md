Related Items
=============

* [Overview](#overview)
* [Classes](#classes)
    * [Config provider](#config-provider)
    * [Strategy](#strategy)
    * [Assigner Strategy](#assigner-strategy)

Overview
--------
Usually, when you sell a product, you also would like to offer your customers
some related products that are a tempting or useful add-on to the product. For example, if you are selling phones, it is good to show fitting accessories next to it. This is where the Related Items functionality comes handy. 

Classes
-------

### Config provider
Class `Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider`
provides you with the configuration for Related Items.

#### Example
You can find example in `Oro\Bundle\ProductBundle\RelatedItem\RelatedProductRelatedProductsConfigProvider`.

Here is the quick overview of its usage:

##### isEnabled()
```php
$this->get('oro_product.related_item.related_product.config_provider')->isEnabled();
```
Returns information on whether the Related Items functionality is enabled.

##### getLimit()
```php
$this->get('oro_product.related_item.related_product.config_provider')->getLimit();
```
Returns integer describing how many related products can be assigned for one product.

##### isBidirectional()
```php
$this->get('oro_product.related_item.related_product.config_provider')->isBidirectional();
```
Returns information about the relation type. To illustrate the difference between uni- and bidirectional relation, let us consider the following example. Product B is a related item of Product A. If bidirectional attribute value is set to true, the product A is also considered as related item of Product B (see [Strategy](#strategy)). This does not apply when the relation is not bidirectional. 

When viewing product A in the front store, a buyer can always see the Product B in related items. 
When viewing product B in the front store, the buyer may or may not see the Product A in related items (depending on the type of relationship).

*isBidirectional == True* and *Product B is a related item of Product A*

| Product          |   Related Items  |
|------------------|------------------|
| Product A        | Product B        |
| Product B        | Product A        |

*isBidirectional == False* and *Product B is a related item of Product A*

| Product          |   Related Items  |
|------------------|------------------|
| Product A        | Product B        |
| Product B        | None             |

For more information about configuration see OroConfigBundle

### Strategy
#### Finder Strategy
The finder strategy (*Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface*) provides related products for a user.
You can see a simple example in *Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\AssignerDatabaseStrategy*
where related products are fetched from the database. This strategy takes into account the configuration from the Config Provider.

For more complex logic, you can create your own strategy.

For this, implement Oro\Bundle\ProductBundle\RelatedItem\Strategy\StrategyInterface, overload or decorate its definition: "oro_product.related_products.strategy", and provide the implementation of the *find* method:

```php
    /**
     * @param Product $product
     * @return Product[]
     */
    public function find(Product $product);
```
where $product is a Product object for which you are searching related products.
The method has to return an array of Products.

#### Assigner Strategy
*Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface*
Use *AssignerStrategyInterface* to store the relation between the product and its related item. 
An example of the implementation can be found in 
*Oro\Bundle\ProductBundle\RelatedItem\RelatedProducts\AssignerDatabaseStrategy*.

It defines two methods:

##### addRelations()
```php
    /**
     * @param Product $productFrom
     * @param Product[] $productsTo
     *
     * @throws \LogicException When functionality is disabled
     * @throws \InvalidArgumentException When a user tries to add a related product to itself
     * @throws \OverflowException When a user tries to add more products than the limit allows
     */
    public function addRelations(Product $productFrom, array $productsTo);
```
The *addRelations* method saves the Related Items relations: from $productFrom to every product provided in $productsTo. The following exceptions may be thrown:

###### LogicException
Should be thrown when Related Items functionality is disabled. 
See [Config provider](#config-provider) for details.

###### InvalidArgumentException
Should be thrown, when a user tries to add product as a related item for itself.

###### OverflowException
Should be thrown when a user tries to add more products that the configured limit allows.
See [Config provider](#config-provider) for details.

##### removeRelations()
```php
    /**
     * @param Product $productFrom
     * @param Product[] $productsTo
     */
    public function removeRelations(Product $productFrom, array $productsTo);
```
The *removeRelations* method removes all Related Item relationships between the product provided in $productsTo and the $productFrom product. When a product in $productsTo is not a Related Item of the $productFrom, no exception should be thrown and the invalid relationship should be skipped. Other Related Items relationships should be processed.  
