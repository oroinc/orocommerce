# Oro\Bundle\ProductBundle\Entity\ProductImageType

## ACTIONS

### create

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productimagetypes",
    "attributes": {
      "productImageTypeType": "main"
    },
    "relationships": {
      "productImage": {
        "data": {
          "type": "productimages",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

#### Validation

The type attribute of the product image type model ("productImageTypeType") should be a valid type
 of image defined in themes and it is not directly handled by the API.

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### update

Edit a specific ProductImageType record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productimagetypes",
    "id": "1",
    "attributes": {
      "productImageTypeType": "additional"
    },
    "relationships": {
      "productImage": {
        "data": {
          "type": "productimages",
          "id": "3"
        }
      }
    }
  },
  "included": [
    {
      "meta": {
        "update": true
      },
      "type": "productimages",
      "id": "3",
      "attributes": {
        "updatedAt": "2017-09-07T08:14:36Z"
      }
    }
  ]
}
```
{@/request}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### productImageTypeType

The type for the productImageType

### productImage

The productImage for the productImageType

## SUBRESOURCES

### productImage

#### get_subresource

Retrieve the productImage of a specific productImageType record. 

#### get_relationship

Retrieve the ID of the productImage for a specific productImageType.

#### update_relationship

Replace the productImage for a specific productImageType.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productimages",
    "id": "3"
  }
}
```
{@/request}
