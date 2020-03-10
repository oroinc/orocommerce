# Oro\Bundle\ProductBundle\Entity\ProductShortDescription

## ACTIONS

### get

Retrieve a specific ProductShortDescription record.

{@inheritdoc}

### get_list

Retrieve a collection of ProductShortDescription records.

{@inheritdoc}

### create

Create a new ProductShortDescription record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productshortdescriptions",
    "attributes": {
      "fallback": null,
      "text": "Short Description"
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

Edit a specific ProductShortDescription record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productshortdescriptions",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "text": "Short Description"
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

Retrieve a record of localization assigned to a specific ProductShortDescription record.

#### get_relationship

Retrieve ID of localization record assigned to a specific ProductShortDescription record.

#### update_relationship

Replace localization assigned to a specific ProductShortDescription record.

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

Retrieve a record of product assigned to a specific ProductShortDescription record.

#### get_relationship

Retrieve ID of product record assigned to a specific ProductShortDescription record.

#### update_relationship

Replace product assigned to a specific ProductShortDescription record.

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
