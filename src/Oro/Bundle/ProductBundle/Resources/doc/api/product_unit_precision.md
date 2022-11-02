# Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision

## ACTIONS

### get

Retrieve a specific product unit precision record.

{@inheritdoc}

### get_list

Retrieve a collection of product unit precision records.

{@inheritdoc}

### create

Create a new product unit precision record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunitprecisions",
    "attributes": {
      "precision": 0,
      "conversionRate": 1,
      "sell": true
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "unit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific product unit precision record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunitprecisions",
    "id": "130",
    "attributes": {
      "precision": 0,
      "conversionRate": 1,
      "sell": true
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "65"
        }
      },
      "unit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific product unit precision record.

{@inheritdoc}

### delete_list

Delete a collection of product unit precision records.

{@inheritdoc}

## FIELDS

### unit

The unit of quantity for the product.

#### create

{@inheritdoc}

**The required field.**

### precision

The precision for the product unit precision.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### product

The product for the product unit precision.

### conversionRate

The conversion rate for the product unit precision.

### sell

The sell flag that tells if the current product unit precision can be sold.

## SUBRESOURCES

### product 

#### get_subresource

Retrieve the product record a specific product unit precision record is assigned to.

#### get_relationship

Retrieve the ID of the product record which a specific product unit precision record is assigned to.

#### update_relationship

Replace the product record a specific product unit precision record is assigned to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### unit 

#### get_subresource

Retrieve a record of product unit assigned to a specific product unit precision record.

#### get_relationship

Retrieve ID of product unit records assigned to a specific product unit precision record.

#### update_relationship

Replace product unit assigned to a specific product unit precision record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}
