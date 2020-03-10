# Oro\Bundle\ProductBundle\Entity\ProductDescription

## ACTIONS

### get

Retrieve a specific ProductDescription record.

{@inheritdoc}

### get_list

Retrieve a collection of ProductDescription records.

{@inheritdoc}

### create

Create a new ProductDescription record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productdescriptions",
    "attributes": {
      "fallback": null,
      "wysiwyg": {
        "value": "Long Description",
        "style": null,
        "properties": null
      }
    },
    "relationships": {
      "localization": {
        "data": {
          "type":"localizations",
          "id":"1"
        }
      },
      "product": {
        "data": {
          "type":"products",
          "id":"1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific ProductDescription record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productdescriptions",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "wysiwyg": {
        "value": "Long Description",
        "style": null,
        "properties": null
      }
    },
    "relationships": {
      "localization": {
        "data": {
          "type":"localizations",
          "id":"1"
        }
      },
      "product": {
        "data": {
          "type":"products",
          "id":"1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

## SUBRESOURCES

### localization

#### get_subresource

Retrieve a record of localization assigned to a specific ProductDescription record.

#### get_relationship

Retrieve ID of localization record assigned to a specific ProductDescription record.

#### update_relationship

Replace localization assigned to a specific ProductDescription record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "localizations",
    "id": "1"
  }
}
```
{@/request}

### product

#### get_subresource

Retrieve a record of product assigned to a specific ProductDescription record.

#### get_relationship

Retrieve ID of product record assigned to a specific ProductDescription record.

#### update_relationship

Replace product assigned to a specific ProductDescription record.

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
