# Low Inventory Highlights

* [Overview](#overview)
* [Configuration](#configuration)
* [Options](#options)
* [Listeners](#listeners)
    * [ProductDatagridListener](#productdatagridlistener)
    * [LowInventoryCheckoutLineItemValidationListener](#lowinventorycheckoutlineitemvalidationlistener)
* [Providers](#providers)
    * [LowInventoryProvider](#lowinventoryprovider)
* [Twig Extensions](#twig-extensions)
    * [LowInventoryExtension](#lowinventoryextension)
* [Validators](#validators)
    * [LowInventoryCheckoutLineItemValidator](#lowinventorycheckoutlineitemvalidator)

## Overview

The low inventory highlights functionality adds an inventory status message to products when their quantity drops below the value defined in Low Inventory Threshold. Reaching the defined Low Inventory Threshold level triggers a warning message to the buyer in the front store.

## Configuration

```yml
product_inventory_options:
    children:
        - oro_inventory.highlight_low_inventory
        - oro_inventory.low_inventory_threshold
```

The `oro_inventory.highlight_low_inventory` option should be used to enable highlighting low inventory for products. Contains the `true` or `false` values.
When the quantity of the product is lower than or equals the value of the `oro_inventory.low_inventory_threshold` option, then the product gets highlighted as low inventory in the front store.

## Options

Two new options were added for products and categories. These options are `highlightLowInventory` and `lowInventoryThreshold`.
These options help configure options for each category or product individually. By default, these options use the value from the system configuration.
To check the currently configured fallback for product or category, please use [Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver](../../../../../../../platform/src/Oro/Bundle/EntityBundle/Fallback/EntityFallbackResolver.php).
Example:

```php
$lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue(
            $product,
            'lowInventoryThreshold'
        );
```
For more details check [LowInventoryProvider](#lowinventoryprovider)

## Listeners

### ProductDatagridListener

This listener contains the method that adds information about low inventory to the product grid.

#### onPreBuild

This method is called before the grid is built. It adds a new `low_inventory` property to the grid configuration that enables adding the low inventory information to the property and thus, displaying it in the layout when required.

#### onResultAfter

This method is called when we execute query and have data result. 
This method uses the logic of [LowInventoryProvider](#lowinventoryprovider). It adds information about the low inventory option for each product in the collection. It also adds a boolean value to `low_inventory` which will then be used in the layout.
The following is an [example](../views/layouts/default/imports/oro_product_grid/low_inventory.html.twig) of using `low_inventory` in the layout of the product grid:
```twig
{% block _product_datagrid_row__product_low_inventory_label_widget %}
    {% if (product.low_inventory) %}
        <div class="grid">
            <div class="grid__row">{{ "oro.inventory.low_inventory.label"|trans }}</div>
        </div>
    {% endif %}
{% endblock %}
```

### LowInventoryCheckoutLineItemValidationListener

The [Oro\Bundle\InventoryBundle\EventListener\LowInventoryCheckoutLineItemValidationListener](../../EventListener/LowInventoryCheckoutLineItemValidationListener.php) class.
This listener contains a method that checks low inventory for line item products and adds a warning message if a product has low quantity.

#### onLineItemValidate

```php
public function onLineItemValidate(LineItemValidateEvent $event)
```
It validates the product from the line item and adds a warning message if this product has a low inventory level.

## Providers

### LowInventoryProvider

The [Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider](../../Inventory/LowInventoryProvider.php) class.

This class contains a method that helps you quickly get information about low quantity for the current product or product collection.

#### isLowInventoryProduct

```php
public function isLowInventoryProduct(Product $product, ProductUnit $productUnit = null)
```
This method returns information about the low inventory status of the current product.  It returns `true` if the quantity of the product is less than the  `lowInventoryThreshold` option.  It returns `false` if the quantity of the product is greater than the `lowInventoryThreshold` option, or if the `highlightLowInventory` is not checked.

#### isLowInventoryCollection

```php
/**
  * Returns low inventory flags for product collection.
  * Will be useful for all product listing (Catalog, Checkout, Shopping list)
  *
  * @param array $data products collection with optional ProductUnit's
  * [
  *     [
  *         'product' => Product Entity,
  *         'product_unit' => ProductUnit entity (optional)
  *     ],
  *     ...
  * ]
  *
  * @return array
  * [
  *      'product id' => bool - has low inventory marker,
  *       ...
  *      'product id' => bool
  * ]
  */
 public function isLowInventoryCollection(array $data)
```

It works in the same way as the [isLowInventoryProduct](#islowinventoryproduct) method, but has differences in taken up arguments and returned value.
This method takes an argument as an array of the [Product](../../../ProductBundle/Entity/Product.php) and [ProductUnit](../../../ProductBundle/Entity/ProductUnit.php) entities and returns an array of product ids with  a boolean result.
`True`  is returned if the quantity of the product is less than the `lowInventoryThreshold` option.  `False` is returned if the quantity of the product is greater than the `lowInventoryThreshold` option, or if `highlightLowInventory` is not checked. 

## Twig Extensions

### LowInventoryExtension

The [Oro\Bundle\InventoryBundle\Twig\LowInventoryExtension](../../Twig/LowInventoryExtension.php) class.

This extension depends on [LowInventoryProvider](#lowinventoryprovider) and provides the oro_is_low_inventory_product twig function which is used in twig templates to check low inventory for a specific product.
The following is an example of using this function in twig templates:

```twig
{% if (oro_is_low_inventory_product(mainProduct)) %}
        <div class="product-low-inventory">{{ "oro.inventory.low_inventory.label"|trans }}</div>
{% endif %}
```

## Validators

### LowInventoryCheckoutLineItemValidator

The [Oro\Bundle\InventoryBundle\Validator\LowInventoryCheckoutLineItemValidator](../../Validator/LowInventoryCheckoutLineItemValidator.php) class.
This class contains a method that returns a message if a product has low quantity.

#### getLowInventoryMessage

```php
public function getLowInventoryMessage(LineItem $lineItem)
```
When a product is marked as low inventory, the method returns a string message. Otherwise, it will return `false`. 
This method uses the logic from [LowInventoryProvider](#lowinventoryprovider). 
