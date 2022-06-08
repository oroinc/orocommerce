# Oro\Bundle\ProductBundle\Entity\ProductKitItem

## ACTIONS

### get

Retrieve a specific product kit item record.

### get_list

Retrieve a collection of product kit item records.

## FIELDS

### label

The localized label of the product kit item.

### productKit

{@inheritdoc}

### products

{@inheritdoc}

### productUnit

{@inheritdoc}

### minimumQuantity

{@inheritdoc}

### maximumQuantity

{@inheritdoc}

### optional

{@inheritdoc}

### sortOrder

{@inheritdoc}

## SUBRESOURCES

### productKit

#### get_subresource

Retrieve the product of type "kit" owning the product kit item.

#### get_relationship

Retrieve the ID of the product of type "kit" owning the product kit item.

### products

#### get_subresource

Retrieve the records for the products of a specific product kit item record.

#### get_relationship

Retrieve a list of IDs for the products of a specific product kit item record.

### productUnit

#### get_subresource

Retrieve the product unit for Minimum Quantity and Maximum Quantity values.

#### get_relationship

Retrieve the ID of the product unit for Minimum Quantity and Maximum Quantity values.
