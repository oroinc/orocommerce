# Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm

## ACTIONS

### get

Retrieve a specific payment term record.

{@inheritdoc}

### get_list

Retrieve a collection of all available payment terms.

{@inheritdoc}

### create

Create a new payment term record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "paymentterms",
    "attributes": {
      "label": "CBS"
    }
  }
}

```
{@/request}

### update

Update payment term label.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "paymentterms",
    "id": "1",
    "attributes": {
      "label": "CBS-new"
    }
  }
}

```
{@/request}

### delete

Delete a specific payment term record.

{@inheritdoc}

### delete_list

Delete a collection of payment term records.

{@inheritdoc}

## FIELDS

### label

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*
