# Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel

## ACTIONS

### get

Retrieve a specific product kit item label record.

### get_list

Retrieve a collection of product kit item label records.

### create

This resource describes a product kit item label entity. It cannot be used independently to create labels.
Product kit item label can only be created together with a product kit item via the product kit item creation API resource.

### update

Edit a specific product kit item label record.

The updated record is present in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productkititemlabels",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "string": "Updated Kit Item Label"
    }
  }
}
```
{@/request}

## FIELDS

### fallback

#### create

{@inheritdoc}

Can be set to the one of the following values: "system", "parent_localization", "null".

### kitItem

#### create

{@inheritdoc}

Cannot be changed once already set.

**The required field.**

#### update

{@inheritdoc}

**The read-only field.**

### localization

#### create

{@inheritdoc}

Cannot be changed once already set.

#### update

{@inheritdoc}

**The read-only field.**

## SUBRESOURCES

### localization

#### get_subresource

Retrieve a record of localization assigned to a specific product kit item label record.

#### get_relationship

Retrieve ID of localization record assigned to a specific product kit item label record.

### kitItem

#### get_subresource

Retrieve a record of product kit item assigned to a specific product kit item label record.

#### get_relationship

Retrieve ID of product kit item record assigned to a specific product kit item label record.
