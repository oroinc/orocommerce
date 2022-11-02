# Oro\Bundle\OrderBundle\Entity\OrderAddress

## ACTIONS

### get

Retrieve a specific order address record.

{@inheritdoc}

### create

This resource describes an order address entity. It cannot be used independently to create addresses.
Addresses can only be created together with an order via the order creation API resource.

{@inheritdoc}

**Note:**
It is sufficient for the submitted order address to have at least one of the following:
a customer user address, or a customer address, or filled in address fields.

## FIELDS

### firstName

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### lastName

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### organization

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### street

#### create

{@inheritdoc}

**The required field.**

### city

#### create

{@inheritdoc}

**The required field.**

### postalCode

#### create

{@inheritdoc}

**The required field.**

### country

#### create

{@inheritdoc}

**The required field.**

### region

#### create

{@inheritdoc}

**Conditionally required field:**
A state is required for some countries.

### customerUserAddress

#### create

{@inheritdoc}

**If specified, data from this address will be copied to the order address.** 

**This field can be passed only if other address fields are empty.**

### customerAddress

#### create

{@inheritdoc}

**If specified, data from this address will be copied to the order address.** 

**This field can be passed only if other address fields are empty.**

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
