# Oro\Bundle\ProductBundle\Entity\Brand

## ACTIONS

### get

Retrieve a specific brand record.

{@inheritdoc}

### get_list

Retrieve a collection of brand records.

{@inheritdoc}

### create

Create a new brand record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "brands",    
    "attributes": {
      "status": "enabled"
    },
    "relationships": { 
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "603"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "localizedfallbackvalues",
      "id": "603",
      "attributes": {
        "fallback": null,
        "string": "Brand",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific brand record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "brands",
    "id": "3",    
    "attributes": {
      "status": "enabled"
    },
    "relationships": { 
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "603"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "localizedfallbackvalues",
      "id": "603",
      "attributes": {
        "string": "BrandNew"
      }
    }
  ]
}
```
{@/request}

### delete

Delete a specific brand record.

{@inheritdoc}

### delete_list

Delete a collection of brand records.

{@inheritdoc}

## FIELDS

### status

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### names

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### descriptions

#### get_subresource

Retrieve the service records that store the brand description localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand description localized data.

#### update_relationship

Replace the ID's of service records that store the brand description localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "44"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the description localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "44"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the description localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "44"
    }
  ]
}
```
{@/request}

### metaDescriptions

#### get_subresource

Retrieve the service records that store the brand meta descriptions localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand meta descriptions localized data.

#### update_relationship

Replace the ID's of service records that store the brand meta descriptions localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "426"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the meta descriptions localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "426"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the meta descriptions localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "426"
    }
  ]
}
```
{@/request}

### metaKeywords

#### get_subresource

Retrieve the service records that store the brand meta keywords localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand meta keywords localized data.

#### update_relationship

Replace the ID's of service records that store the brand meta keywords localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "427"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the meta keywords localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "427"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the meta keywords localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "427"
    }
  ]
}
```
{@/request}

### metaTitles

#### get_subresource

Retrieve the service records that store the brand meta titles localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand meta titles localized data.

#### update_relationship

Replace the ID's of service records that store the brand meta titles localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "428"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the meta titles localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "428"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the meta titles localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "428"
    }
  ]
}
```
{@/request}

### names

#### get_subresource

Retrieve the service records that store the brand names localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand names localized data.

#### update_relationship

Replace the ID's of service records that store the brand names localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "43"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the names localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "43"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the names localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "43"
    }
  ]
}
```
{@/request}

### shortDescriptions

#### get_subresource

Retrieve the service records that store the brand short descriptions localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand short descriptions localized data.

#### update_relationship

Replace the ID's of service records that store the brand short descriptions localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "45"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the short descriptions localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "45"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the short descriptions localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "45"
    }
  ]
}
```
{@/request}

### slugPrototypes

#### get_subresource

Retrieve the service records that store the brand slug prototypes localized data.

#### get_relationship

Retrieve the ID's of service records that store the brand slug prototypes localized data.

#### update_relationship

Replace the ID's of service records that store the brand slug prototypes localized data.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "46"
    }
  ]
}
```
{@/request}

#### add_relationship

Set relationship with the service records that store the slug prototypes localized data for the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "46"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove service records that store the slug prototypes localized data from the brand record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "46"
    }
  ]
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific brand record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific brand record will belong to.

#### update_relationship

Replace the organization a specific brand record belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### owner

#### get_subresource

Retrieve the record of the business unit who is an owner of a specific brand record.

#### get_relationship

Retrieve the ID of the business unit which is an owner of a specific brand record.

#### update_relationship

Replace the owner of a specific brand record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "businessunits",
    "id": "1"
  }
}
```
{@/request}
