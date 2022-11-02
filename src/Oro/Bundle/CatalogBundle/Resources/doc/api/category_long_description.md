# Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription

## ACTIONS

### get

Retrieve a specific category long description record.

{@inheritdoc}

### get_list

Retrieve a collection of category long description records.

{@inheritdoc}

### create

Create a new category long description record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categorylongdescriptions",
    "attributes": {
      "fallback": null,
      "wysiwyg": "Long Description"
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific category long description record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categorylongdescriptions",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "wysiwyg": "Long Description"
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "1"
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

Retrieve a record of localization assigned to a specific category long description record.

#### get_relationship

Retrieve ID of localization record assigned to a specific category long description record.

#### update_relationship

Replace localization assigned to a specific category long description record.

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

### category

#### get_subresource

Retrieve a record of category assigned to a specific category long description record.

#### get_relationship

Retrieve ID of category record assigned to a specific category long description record.

#### update_relationship

Replace category assigned to a specific category long description record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categories",
    "id": "1"
  }
}
```
{@/request}
