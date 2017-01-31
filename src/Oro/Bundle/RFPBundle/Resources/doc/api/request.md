# Oro\Bundle\RFPBundle\Entity\Request

## ACTIONS

### get

Get one Request record.

{@inheritdoc}

### get_list

Returns a collection of Requests

{@inheritdoc}

### create

Create a new Request. The fields `internal_status`, `customer_status`, `requestAdditionalNotes` are not configurable 
by api, because it depends on the workflow states.

{@inheritdoc}

### update

Update existing Request record. The fields `internal_status`, `customer_status`, `requestAdditionalNotes`, 
`createdAt` - can not be updated.

{@inheritdoc}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### firstName

#### create

{@inheritdoc}

**The required field**

### lastName

#### create

{@inheritdoc}

**The required field**

### email

#### create

{@inheritdoc}

**The required field**

### company

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### requestProducts

#### get_subresource

Get full information about the product of request for quote.

### contact

#### get_subresource

Get full information about the person on the customer side who is directly related to the opportunity.

### requestAdditionalNotes

#### get_subresource

Get list of additional notes.

### assignedUsers

#### get_subresource

Get full information about assigned users.

### organization

#### get_subresource

Get full information about an organization to which the request belongs.

### owner

#### get_subresource

Get full information about an user who owns the request.
