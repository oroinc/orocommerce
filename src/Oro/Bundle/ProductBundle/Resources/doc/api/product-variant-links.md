# Oro\Bundle\ProductBundle\Entity\ProductVariantLink

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

{@inheritdoc}

### update

{@inheritdoc}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### parentProduct

The configurable product that has the variants

### product

The product that will be the variant of a configurable product

### visible

The visibility of the variant

## SUBRESOURCES

### parentProduct

#### get_subresource

Retrieve the configurable product configured for a specific variant

#### get_relationship

Retrieve an ID of the configurable product

#### update_relationship

Replace the configurable product for a specific variant

### product

#### get_subresource

Retrieve the product that is set as a variant

#### get_relationship

Retrieve an ID of the variant product

#### update_relationship

Update the product for a specific variant