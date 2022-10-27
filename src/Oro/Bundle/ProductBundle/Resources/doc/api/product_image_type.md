# Oro\Bundle\ProductBundle\Entity\ProductImageType

## ACTIONS

### get

Retrieve a specific product image type record.

{@inheritdoc}

### get_list

Retrieve a collection of product image type records.

{@inheritdoc}

### create

Create a new product image type record.

The created record is returned in the response.

{@inheritdoc}

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

### update

Edit a specific product image type record.

The updated record is returned in the response.

{@inheritdoc}

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

Delete a specific product image type record.

{@inheritdoc}

### delete_list

Delete a collection of product image type records.

{@inheritdoc}

## FIELDS

### productImageTypeType

The type for the product image.

### productImage

The associated product image.

## SUBRESOURCES

### productImage

#### get_subresource

Retrieve the record of the product image a specific product image type record is associated with.

#### get_relationship

Retrieve the ID of the product image a specific product image type record is associated with.

#### update_relationship

Replace the product image a specific product image type record is associated with.

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
