# Oro\Bundle\CheckoutBundle\Api\Model\CheckoutAddress

## ACTIONS

### get

Retrieve a specific checkout address record.

### create

This resource describes a checkout address entity. It cannot be used independently to create addresses.
Addresses can only be created together with a checkout via the checkout creation API resource.

**Note:**
It is sufficient for the submitted checkout address to have at least one of the following:
a customer user address, or a customer address, or filled in address fields.

### update

Edit a specific checkout address record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutaddresses",
    "id": "1",
    "attributes": {
      "firstName": "Amanda",
      "lastName": "Cole"
    }
  }
}
```
{@/request}

## FIELDS

### label

The checkout address label (e.g., Main Office).

### createdAt

The date and time when the checkout address was created.

#### create, update

The date and time when the checkout address was created.

**The read-only field. A passed value will be ignored.**

### updatedAt

The date and time when the checkout address was updated.

#### create, update

The date and time when the checkout address was updated.

**The read-only field. A passed value will be ignored.**

### namePrefix

Customer user's name prefix provided in the checkout shipping or billing address.

### nameSuffix

Customer user's name suffix provided in the checkout shipping or billing address.

### firstName

Customer user's first name provided in the checkout shipping or billing address.

#### create, update

Customer user's first name provided in the checkout shipping or billing address.

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### middleName

Customer user's middle name provided in the checkout shipping or billing address.

### lastName

Customer user last name provided in the checkout shipping or billing address.

#### create, update

Customer user last name provided in the checkout shipping or billing address.

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### organization

The organization that is specified in the checkout address.

#### create, update

The organization that is specified in the checkout address.

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

### street

The street address provided in the checkout shipping or billing address.

#### create, update

The street address provided in the checkout shipping or billing address.

**The required field.**

### street2

The street address provided in the checkout shipping or billing address.

### city

The city provided in the checkout shipping or billing address.

#### create, update

The city provided in the checkout shipping or billing address.

**The required field.**

### postalCode

The postal code provided in the checkout shipping or billing address.

#### create, update

The postal code provided in the checkout shipping or billing address.

**The required field.**

### country

The country provided in the checkout shipping or billing address.

#### create, update

The country provided in the checkout shipping or billing address.

**The required field.**

### region

The state or region provided in the checkout shipping or billing address.

#### create, update

The state or region provided in the checkout shipping or billing address.

**Conditionally required field:**
A state is required for some countries.

### phone

A customer user phone number provided for the checkout address.

### customerUserAddress

The identifier of customer user address, which was used as checkout address.

#### create, update

The identifier of customer user address, which was used as checkout address.

**If specified, data from this address will be copied to the checkout address.** 

**This field can be passed only if other address fields are empty.**

### customerAddress

The identifier of customer address, which was used as checkout address.

#### create, update

The identifier of customer address, which was used as checkout address.

**If specified, data from this address will be copied to the checkout address.** 

**This field can be passed only if other address fields are empty.**

## SUBRESOURCES

### country

#### get_subresource

Retrieve a record of country assigned to a specific address record.

#### get_relationship

Retrieve the ID of country record assigned to a specific address record.

### region

#### get_subresource

Retrieve a record of region assigned to a specific region record.

#### get_relationship

Retrieve IDs of region records assigned to a specific region record.

### customerAddress

#### get_subresource

Retrieve a record of customer address assigned to a specific checkout address record.

#### get_relationship

Retrieve the ID of customer address record assigned to a specific checkout address record.

### customerUserAddress

#### get_subresource

Retrieve a record of customer user address assigned to a specific checkout address record.

#### get_relationship

Retrieve the ID of customer user address record assigned to a specific checkout address record.
