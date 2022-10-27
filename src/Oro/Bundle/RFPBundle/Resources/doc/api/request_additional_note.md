# Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote

## ACTIONS

### get

Retrieve a specific request additional note record.

{@inheritdoc}

### get_list

Retrieve a collection of request additional note records.

{@inheritdoc}

## FIELDS

### requestAdditionalNoteType

{@inheritdoc}
Can be `seller_note` for a Request More Information note from the sales person or `customer_note` for a Provide More Information note from the buyer.

## SUBRESOURCES

### request

#### get_subresource

Retrieve the request record a specific additional note record is assigned to.

#### get_relationship

Retrieve the ID of request record a specific additional note record is assigned to.
