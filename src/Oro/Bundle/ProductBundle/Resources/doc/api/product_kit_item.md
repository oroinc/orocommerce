# Oro\Bundle\ProductBundle\Entity\ProductKitItem

## ACTIONS

### get

Retrieve a specific product kit item record.

### get_list

Retrieve a collection of product kit item records.

### create

Create a new product kit item record.

The created record is present in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productkititems",
    "id":"productkititem-1",
    "attributes": {
      "optional": false
    },
    "relationships": {
      "productKit": {
        "data": {
          "type": "products",
          "id": "42"
        }
      },
      "labels": {
          "data": [
            {
              "type": "productkititemlabels",
              "id": "productkititemlabel-1"
            }
          ]
      },
      "kitItemProducts": {
          "data": [
            {
              "type": "productkititemproducts",
              "id": "productkititemproduct-1"
            }
          ]
      },
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  },
  "included": [
    {
      "type": "productkititemlabels",
      "id": "productkititemlabel-1",
      "attributes": {
        "fallback": null,
        "string": "Product Kit Item 1 Label"
      },
      "relationships": {
        "productKitItem": {
          "data": {
            "type": "productkititems",
            "id": "productkititem-1"
          }
        }
      }
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-1",
      "attributes":{
        "sortOrder": "1"
      },
      "relationships": {
        "kitItem": {
          "data": {
            "type": "productkititems",
            "id": "productkititem-1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "4242"
          }
        }
      }
    }
  ]
}
```
 {@/request}

### update

Edit a specific product kit item record.

The updated record is present in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productkititems",
    "id": "1",
    "attributes": {
      "optional": true
    }
  }
}
```
 {@/request}

### delete

Delete a specific product kit item record.

The last kit item cannot be deleted from the product kit.

### delete_list

Delete a collection of product kit items records.

The last kit item cannot be deleted from the product kit.

## FIELDS

### labels

#### create

{@inheritdoc}

**The required field.**

### productKit

#### create

{@inheritdoc}

Cannot be changed once already set.

**The required field.**

#### update

{@inheritdoc}

**The read-only field.**

### kitItemProducts

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

### productUnit

#### create

{@inheritdoc}

Must be available among all products of this kit item.

**The required field.**

#### update

{@inheritdoc}

Must be available among all products of this kit item.

### minimumQuantity

{@inheritdoc}

### maximumQuantity

{@inheritdoc}

### optional

#### create

{@inheritdoc}

False by default.

### sortOrder

{@inheritdoc}

## SUBRESOURCES

### labels

#### get_subresource

Retrieve the records for the labels of a specific product kit item record.

#### get_relationship

Retrieve a list of IDs for the labels of a specific product kit item record.

### productKit

#### get_subresource

Retrieve the product of type "kit" owning the product kit item.

#### get_relationship

Retrieve the ID of the product of type "kit" owning the product kit item.

### kitItemProducts

#### get_subresource

Retrieve the kit item products records for a specific product kit item record.

#### get_relationship

Retrieve a list of IDs for the kit item products of a specific product kit item record.

#### add_relationship

Set the kit item products for a specific product kit item record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-1"
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-2"
    }
  ],
  "included": [
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-1",
      "attributes":{
        "sortOrder": "1"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "4242"
          }
        }
      }
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-2",
      "attributes":{
        "sortOrder": "2"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "4343"
          }
        }
      }
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the kit item products for a specific product kit item. The collection cannot be empty.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititemproducts",
      "id": "productkititemproducts-3"
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproducts-4"
    }
  ],
  "included": [
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-3",
      "attributes":{
        "sortOrder": "1"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "4242"
          }
        }
      }
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-4",
      "attributes":{
        "sortOrder": "2"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "4343"
          }
        }
      }
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the kit item products from a specific product kit item record.

The last kit item product cannot be deleted from the product kit item.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-1"
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-2"
    }
  ]
}
```
{@/request}

### productUnit

#### get_subresource

Retrieve the product unit for Minimum Quantity and Maximum Quantity values.

#### get_relationship

Retrieve the ID of the product unit for Minimum Quantity and Maximum Quantity values.

#### update_relationship

Replace the product unit for Minimum Quantity and Maximum Quantity values.

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
