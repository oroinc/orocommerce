# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### get

Retrieve a specific product record.

{@inheritdoc}

### get_list

Retrieve a collection of product records.

{@inheritdoc}

## FIELDS

### name

The localized name of the product.

### shortDescription

The localized short description of the product.

### description

The localized description of the product.

### unitPrecisions

An array of precisions for each product unit selected for the product.

Each element of the array is an object with the following properties:

**unit** is a string that contains the ID of the product unit.

**precision** is a number of digits after the decimal point for the number of products that a customer
can order or add to the shopping list.

**conversionRate** is a number that contains a conversion rate to convert from this unit to the default unit.

**default** is a boolean that indicates whether this unit is default or not for the product.

Example of data: **\[{"unit": "item", "precision": 0, "conversionRate": 1, "default": true}\]**

### productAttributes

An object contains all visible product attributes and attributes required to choose a variant
for configurable products, even if such attributes are invisible.

In case an attribute is a to-one relationship the value is an object with two properties:

**id** is a string that contains ID of the related entity.

**targetValue** is a string representation of the related entity.

In case an attribute is a to-many relationship the value is an array of objects described above.

Example of data: **{"stringAttribute": "test", "toOneRelationshipAttribute": {"id": "1", "targetValue": "test"}}**

### variantProducts

The products that are variants for a configurable product.

### parentProducts

The configurable products that use this product as a variant.

### kitItems

Items of the product kit.

### images

The images of the product.

### url

The relative URL of the product for the current localization.

### urls

An array of product urls for all localizations except the current localization.

Each element of the array is an object with the following properties:

**url** is a string that contains the relative URL of the product.

**localizationId** is a string that contains ID of the localization the url is intended for.

Example of data: **\[{"url": "/en-url", "localizationId": "10"}, {"url": "/fr-url", "localizationId": "11"}\]**

## FILTERS

### variants

The filter that allows to exclude simple products which are variants for configurable products. Use "no", "false" or "0" value to apply it.

## SUBRESOURCES

### productFamily

#### get_subresource

Retrieve a record of the family that a specific product belongs to.

#### get_relationship

Retrieve the ID of the family that a specific product belongs to.

### variantProducts

#### get_subresource

Retrieve records of products that are variants for a specific configurable product.

#### get_relationship

Retrieve the IDs of products that are variants for a specific configurable product.

### parentProducts

#### get_subresource

Retrieve records of configurable products for which a specific product is a variant.

#### get_relationship

Retrieve the IDs of configurable products for which a specific product is a variant.

### images

#### get_subresource

Retrieve records of product images for a specific product.

#### get_relationship

Retrieve the IDs of product images for a specific product.

### inventoryStatus

#### get_subresource

Retrieve a record of the inventory status for a specific product.

#### get_relationship

Retrieve the ID of the inventory status for a specific product.

### kitItems

#### get_subresource

Retrieve the product kit items of a specific product record.

#### get_relationship

Retrieve a list of IDs for the product kit items of a specific product record.
