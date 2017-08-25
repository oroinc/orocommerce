# Oro\Bundle\ProductBundle\Entity\ProductImageType

## ACTIONS

### create

{@inheritdoc}

##### Validation

The type attribute of the product image type model ("productImageTypeType") should be a valid type
 of image defined in themes and it is not directly handled by the API.

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### update

{@inheritdoc}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### productImageTypeType

The type for the productImageType

### productImage

The productImage for the productImageType

## SUBRESOURCES

### productImage

#### get_subresource

Retrieve the productImage of a specific productImageType record. 

#### get_relationship

Retrieve the ID of the productImage for a specific productImageType.

#### update_relationship

Replace the productImage for a specific productImageType.
