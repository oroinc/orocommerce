# Oro\Bundle\OrderBundle\Entity\OrderAddress

## ACTIONS

### get

Retrieve a specific order address record.

{@inheritdoc}

### get_list

Retrieve a collection of order address records.

{@inheritdoc}

## SUBRESOURCES

### country

#### get_subresource

Retrieve a record of country assigned to a specific address record.

#### get_relationship

Retrieve ID of country record assigned to a specific address record.

### region

#### get_subresource

Retrieve a record of region assigned to a specific region record.

#### get_relationship

Retrieve IDs of region records assigned to a specific region record.

### customerAddress

#### get_subresource

Retrieve a record of customer address assigned to a specific order address record.

#### get_relationship

Retrieve the ID of customer address record assigned to a specific order address record.

### customerUserAddress

#### get_subresource

Retrieve a record of customer user address assigned to a specific order address record.

#### get_relationship

Retrieve the ID of customer user address record assigned to a specific order address record.
