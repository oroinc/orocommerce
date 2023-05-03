# Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions

## ACTIONS

### get

Retrieve a specific product shipping option record.

{@inheritdoc}

### get_list

Retrieve a collection of product shipping option records.

{@inheritdoc}

### create

Create a new product description record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productshippingoptions",
    "attributes": {
      "weightValue": 9,
      "dimensionsLength": 9.9,
      "dimensionsWidth": 90.09,
      "dimensionsHeight": 0.9
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "64"
        }
      },
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "set"
        }
      },
      "weightUnit": {
        "data": {
          "type": "weightunits",
          "id": "kg"
        }
      },
      "dimensionsUnit": {
        "data": {
          "type": "lengthunits",
          "id": "m"
        }
      },
      "freightClass": {
        "data": {
          "type": "freightclasses",
          "id": "parcel"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific product shipping option record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productshippingoptions",
    "id": "1",
    "attributes": {
      "weightValue": 6,
      "dimensionsLength": 7.7,
      "dimensionsWidth": 80.08,
      "dimensionsHeight": 0.99
    },
    "relationships": {
      "weightUnit": {
        "data": {
          "type": "weightunits",
          "id": "lbs"
        }
      },
      "dimensionsUnit": {
        "data": {
          "type": "lengthunits",
          "id": "m"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific product shipping option record.

{@inheritdoc}

### delete_list

Delete a collection of product shipping option records.

{@inheritdoc}

## FIELDS

### dimensionsHeight

The maximum height of a product shipping option.

### dimensionsLength

The maximum length of a product shipping option.

### dimensionsWidth

The maximum width of a product shipping option.

### weightValue

The maximum weight value of a product shipping option.

### dimensionsUnit

The dimension unit of a product shipping option.

### freightClass

The freight class of a product shipping option.

### product

The product of a product shipping option.

### productUnit

The product unit of a product shipping option.

### weightUnit

The weight unit of a product shipping option.

## SUBRESOURCES

### dimensionsUnit

#### get_subresource

Retrieve the record of the dimension unit a specific product shipping option record is associated with.

#### get_relationship

Retrieve the ID of the dimension unit a specific product shipping option record is associated with.

#### update_relationship

Replace the dimension unit a specific product shipping option record is associated with.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "lengthunits",
    "id": "m"
  }
}
```
{@/request}

### freightClass

#### get_subresource

Retrieve the record of the freight class a specific product shipping option record is associated with.

#### get_relationship

Retrieve the ID of the freight class a specific product shipping option record is associated with.

#### update_relationship

Replace the freight class a specific product shipping option record is associated with.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "freightclasses",
    "id": "parcel"
  }
}
```
{@/request}

### product

#### create

{@inheritdoc}

**The required field.**

#### get_subresource

Retrieve a record of product assigned to a specific product shipping option record.

#### get_relationship

Retrieve ID of product record assigned to a specific product shipping option record.

#### update_relationship

Replace product assigned to a specific product shipping option record.

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

### productUnit

#### create

{@inheritdoc}

**The required field.**

#### get_subresource

Retrieve the record of the product unit a specific product shipping option record is associated with.

#### get_relationship

Retrieve the ID of the product unit a specific product shipping option record is associated with.

#### update_relationship

Replace the product unit a specific product shipping option record is associated with.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "set"
  }
}
```
{@/request}

### weightUnit

#### get_subresource

Retrieve the record of the weight unit a specific product shipping option record is associated with.

#### get_relationship

Retrieve the ID of the weight unit a specific product shipping option record is associated with.

#### update_relationship

Replace the weight unit a specific product shipping option record is associated with.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "weightunits",
    "id": "item"
  }
}
```
{@/request}
