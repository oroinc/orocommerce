# Oro\Bundle\ProductBundle\Entity\ProductImage

## ACTIONS

### create

{@inheritdoc}

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

### product

The product for the productImage

### types

The imaget types for the productImage

### image

The image file for the productImage

## SUBRESOURCES

### product

#### get_subresource

Retrieve product of a specific productImage record. 

#### get_relationship

Retrieve the ID of the product for a specific productImage.

#### update_relationship

Replace the product for a specific productImage.

### types

#### get_subresource

Retrieve the records for the types of a specific productImage record.

#### get_relationship

Retrieve a list of IDs for the types of a specific productImage record. 

#### update_relationship

Replace the types for a specific productImage.

#### add_relationship

Set the types of a specific productImage record.

#### delete_relationship

Remove the types of a specific productImage record.

### image

#### get_subresource

Retrieve the image file of a specific productImage record.  

#### get_relationship

Retrieve the ID of the image file for a specific productImage.

#### update_relationship

Replace the image file for a specific productImage.
