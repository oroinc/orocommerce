# Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm

## ACTIONS

### get

Retrieve a single payment term (identified by id) that includes information about its custom label.

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically cover a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

### get_list

Retrieve a collection of all available payment terms. An item in the collection is a single payment term (identified by id) that includes information about its custom label.

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically cover a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

### create

Create a new payment term. A newly created record is returned in the response.

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically covers a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

{@request:json_api}

Example:

`</admin/api/paymentterms>`

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

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically cover a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

{@request:json_api}

Example:

`</admin/api/paymentterms>`

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

Delete a payment term identified by id.

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically cover a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

{@inheritdoc}

### delete_list

Delete a collection of payment terms.

<b>Definition:</b> Payment terms are the conditions that are used to complete a sale and typically cover a payment due date (e.g. payment ten days after invoice date) and/or payment conditions (e.g. cash before shipment).

## FIELDS

### label

A custom label that describes a payment term, e.g. *Net 10*, *COD* or *CWO*.
