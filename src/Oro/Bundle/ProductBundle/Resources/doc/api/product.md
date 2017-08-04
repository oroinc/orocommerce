# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

Create a new Product record.
The created record is returned in the response.

##### 1. Static options for product attributes

| Attribute| Options | Description |
|----------|---------|-------------|
| decrementQuantity | 1 / 0 | Yes / No |
| manageInventory | 1 / 0 | On Order Submission / Defined by Workflow |
| backOrder | 1 / 0 | Yes / No |
| productType | "simple" / "configurable" | |
| status | "enabled" / "disabled" |  |
| featured | true / false |  |
| newArrival | true / false |  |
| | | |

##### 2. Creating related entities together with the Product entity:

When creating a Product entity there are certain relations or associations with other entities
which require by default that you specify their type and id so that they are loaded.

But if you need to create a new entity from a relation, you have the option to do so, but you must
use the **"included"** section. See documentation about this section [here](https://www.orocrm.com/documentation/current/book/data-api#create-and-update-related-resources-together-with-a-primary-api-resource)

For example, if we look at the manageInventory field, in the **"data"** section

      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      }

we have a temporary ID (recomended to be a GUID, as the documentation says) "1abcd" which is
used to identify the data used for creating this entity, data taken from the **"included"** section :

    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    }

The same applies to other relation entities like "localizedfallbackvalues" type, which are used for
properties like "names", "descriptions", "shortDescriptions", and also meta fields. You can see in
the detailed example for product creation.

##### 3. Using ProductPrecisionUnits:

A ProductPrecisionUnit is a relation between the product and unit of quantity and other details like
conversion rates. Also, there is the concept of the primary ProductPrecisionUnit which is actually the
default precision unit, and it is mandatory.

There are a few restrictions and situations that the API caller should know:

- not sending the **"primaryUnitPrecision"** will make the first item in the **"unitPrecisions"** list become
the primary unit precision

- when sending the "primaryUnitPrecision" you need to specify the unit code, but it is mandatory that
this unit code is found between the items of the **"unitPrecisions"**

##### 4. Specify Category

The Category is not directly handled by the Product, but you can specify it when creating or updating
a Product entity, in the **"data"** section. Example:

      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      }

You can see the existing categories using its API [here](#get--admin-api-categories)

{@request:json_api}

Example:

`</admin/api/products>`

```JSON
{
  "data":
  {
    "type": "products",
    "attributes": {
      "sku": "test-api-1",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "names-1"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "names-2"
          }
        ]
      },
      "slugPrototypes": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "slug-id-1"
          }
        ]
      },
      "taxCode": {
        "data": {
          "type": "producttaxcodes",
          "id": "2"
        }
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "primaryUnitPrecision":{
        "data": {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-3"
        }
      },
      "unitPrecisions": {
        "data": [
          {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-1"
          },
          {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-2"
          }
        ]
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "out_of_stock"
        }
      },
      "pageTemplate": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1xyz"
        }
      },
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      },
      "inventoryThreshold": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "2abcd"
        }
      },
      "minimumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "3abcd"
        }
      },
      "maximumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "4abcd"
        }
      },
      "decrementQuantity": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "5abcd"
        }
      },
      "backOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "6abcd"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      }
    }
  },
  "included":[
    {
      "type": "entityfieldfallbackvalues",
      "id": "1xyz",
      "attributes": {
        "fallback": null,
        "scalarValue": "short",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "2abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "31",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "3abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "4abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "12",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "5abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "1",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "6abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "0",
        "arrayValue": null
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "names-1",
      "attributes": {
        "fallback": null,
        "string": "Test product",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "names-2",
      "attributes": {
        "fallback": null,
        "string": "Product in spanish",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "6"
          }
        }
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "slug-id-1",
      "attributes": {
        "fallback": null,
        "string": "test-prod-slug",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-1",
      "attributes": {
          "precision": "0",
          "conversionRate": "5",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "each"
          }
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-2",
      "attributes": {
          "precision": "0",
          "conversionRate": "10",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-3",
      "attributes": {
          "precision": "0",
          "conversionRate": "2",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        }
      }
    }

  ]
}

```
{@/request}

### update

Edit a specific Product record. [See product create](#post--admin-api-products) documentation for examples
and explanations.

Other details

##### 1. Using ProductPrecisionUnits:

Besides what it is mentioned in the create Product section above, for the ProductPrecisionUnits
there are a few more restrictions and situations that the API caller should know:

- inside an item of the **"unitPrecisions"** list, if we have the "id" field, the only mandatory
field remaining mandatory is the "unit_code"
the primary unit precision

- if we send in the list of **"unitPrecisions"** a new "unit_code" that is not found among the
list of unit precisions on the Product main entity, then it will be created and added to the list

- if in the **"primaryUnitPrecision"** we send a new "unit_code", other then what it is found on
the current product primary unit precision, then the current primary unit precision will be replaced
with the newly provided one

**Important** - when updating product unit precisions you need to pay attention to:

- specify all unit precisions for the product (including primary), even though you want to update the attributes for
only one of them

- in the "included" section specify the product unit that you want to update but don't forget to use the same id as it has in the
database (id which you use in the "data" section on the field) and specify the "update" field in the "meta" subsection
of the "included" section (see example below)


##### 2. Updating "localizedfallbackvalues" (localized fields) and "entityfieldfallbackvalues" (options with fallbacks) types

**Important** - When you want to update existing related entities, it can only be done by using the
"included" section, the same way it is used in the create section. What is important to mention is:

* you must use the real ID of the related entity that you want to update, example:

> - "data" section

        "manageInventory": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "id": "466"
          }
        }

> - "included" section

        {
          "meta":{
             "update": true
          },
          "type": "localizedfallbackvalues",
          "id": "807",
          "attributes": {
            "fallback": null,
            "string": "Test product - updated",
            "text": null
          }
        }

* use the update flag to specify it is an update on an existing entity, otherwise it will attempt
the creation of a new entity of that type

          "meta":{
             "update": true
          }

**Important** when wanting to update the current entity by modifying a relation, which is actually
a'to-many' relationship with another entity, you must specify all of the entities from that list.
If you don't do that, the system will set on that relation the input that has been received. So for
example if I have the "names" relation which holds a collection of "localizedfallbackvalues" type,
with 8 entities, and I specify only 2 of these entities in the input, then in the database I will
have only those 2 saved and all the other (6 entities) will be removed. Example for updating the
"names" relation with modifying the text for a specific localization:

> - in the "data" section:

      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "807"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "810"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "814"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "812"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "813"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "811"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "808"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "815"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "816"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "817"
          }
        ]

> - in the "included" section:

    {
      "meta":{
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "807",
      "attributes": {
        "fallback": null,
        "string": "Test product - updated",
        "text": null
      }
    }

{@request:json_api}
Example:

`</admin/api/products/12>`

```JSON
{
  "data":
  {
    "type": "products",
    "id": "67",
    "attributes": {
      "sku": "test-api-3",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "taxCode": {
        "data": {
          "type": "producttaxcodes",
          "id": "2"
        }
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "in_stock"
        }
      },
      "pageTempalte": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "355"
        }
      },
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "466"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      },
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "807"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "810"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "814"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "812"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "813"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "811"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "808"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "815"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "816"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "817"
          }
        ]
      },
      "primaryUnitPrecision":{
        "data": {
            "type": "productunitprecisions",
            "id": "453"
        }
      },
      "unitPrecisions": {
        "data": [
          {
            "type": "productunitprecisions",
            "id": "454"
          },
          {
            "type": "productunitprecisions",
            "id": "455"
          }
        ]
      }
    }
  },
  "included":[
    {
      "meta":{
         "update": true
      },
      "type": "entityfieldfallbackvalues",
      "id": "466",
      "attributes": {
        "fallback": null,
        "scalarValue": "0",
        "arrayValue": null
      }
    },
    {
      "meta":{
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "807",
      "attributes": {
        "fallback": null,
        "string": "Test product - updated",
        "text": null
      }
    },
    {
      "type": "productunitprecisions",
      "id": "453",
      "attributes": {
          "precision": "7",
          "conversionRate": "5",
          "sell": "0"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        }
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

### sku

#### create, get, get_list

{@inheritdoc}

**Required field**

### skuUppercase

#### create, get, get_list, update

{@inheritdoc}

### names

#### create, get, get_list

{@inheritdoc}

**Required field**

### decrementQuantity

#### create, get, get_list

{@inheritdoc}

**Required field**

### inventoryThreshold

#### create, get, get_list

{@inheritdoc}

**Required field**

### inventory_status

#### create, get, get_list

{@inheritdoc}

**Required field**

### manageInventory

#### create, get, get_list

{@inheritdoc}

**Required field**

### backOrder

#### create, get, get_list, update

{@inheritdoc}

**Required field**

### status

#### create, get, get_list

{@inheritdoc}

**Required field**

### featured

#### create, get, get_list

{@inheritdoc}

**Required field**

### newArrival

#### create, get, get_list

{@inheritdoc}

**Required field**

### productType

#### create, get, get_list

{@inheritdoc}

**Required field**

### attributeFamily

#### create, get, get_list

{@inheritdoc}

**Required field**

### category

#### create, get, get_list

{@inheritdoc}

Specify the category of the product

### taxCode

#### create, get, get_list, update

{@inheritdoc}

### test_variant_field

#### create, get, get_list, update

{@inheritdoc}

## SUBRESOURCES

### attributeFamily

#### get_subresource

Retrieve the attribute family configured for a specific product

#### get_relationship

Retrieve an ID of the attribute family that a specific product record belongs to.

#### update_relationship

Replace the attributeFamily for a specific product record.

### backOrder

#### get_subresource

Retrieve the record of the fallback entity value for backOrder for a specific product

#### get_relationship

Retrieve an ID of the backOrder flag

#### update_relationship

Replace the backOrder entity fallback value for a specific product record.

### brand

#### get_subresource

Retrieve the brand for a specific product

#### get_relationship

Retrieve an ID of the brand of the product

#### update_relationship

Replace the brand for a specific product record.

### decrementQuantity

#### get_subresource

Retrieve the record of the fallback entity value for decrementQuantity flag for a specific product

#### get_relationship

Retrieve an ID of the decrementQuantity flag for a specific product

#### update_relationship

Replace the decrementQuantity entity fallback value for a specific product record.

### inventoryThreshold

#### get_subresource

Retrieve the record of the fallback entity value for inventoryThreshold for a specific product

#### get_relationship

Retrieve an ID of the inventoryThreshold for a specific product

#### update_relationship

Replace the inventoryThreshold entity fallback value for a specific product record.

### inventory_status

#### get_subresource

Retrieve the record of the fallback entity value for inventory_status flag for a specific product

#### get_relationship

Retrieve an ID of the inventory_status flag for a specific product

#### update_relationship

Replace the inventory_status for a specific product record.

### manageInventory

#### get_subresource

Retrieve the record of the fallback entity value for manageInventory flag for a specific product

#### get_relationship

Retrieve an ID of the manageInventory flag for a specific product

#### update_relationship

Replace the manageInventory entity fallback value for a specific product record.

### maximumQuantityToOrder

#### get_subresource

Retrieve the record of the fallback entity value for maximumQuantityToOrder for a specific product

#### get_relationship

Retrieve an ID of the maximumQuantityToOrder for a specific product

#### update_relationship

Replace the maximumQuantityToOrder entity fallback value for a specific product record.

### minimumQuantityToOrder

#### get_subresource

Retrieve the record of the fallback entity value for minimumQuantityToOrder for a specific product

#### get_relationship

Retrieve an ID of the minimumQuantityToOrder for a specific product

#### update_relationship

Replace the minimumQuantityToOrder entity fallback value for a specific product record.

### pageTemplate

#### get_subresource

Retrieve the record of the fallback entity value for pageTemplate for a specific product

#### get_relationship

Retrieve an ID of the pageTemplate value used for a specific product

#### update_relationship

Replace the backOrder entity fallback value for a specific product record.

### owner

#### get_subresource

Retrieve the records of the product which is the owner of a specific product record.

#### get_relationship

Retrieve an ID of the user who is the owner of a specific product record.

#### update_relationship

Replace the owner of a specific product record.

### organization

#### get_subresource

Retrieve the record of the organization a specific product record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific product record belongs to.

#### update_relationship

Replace the organization that a specific product belongs to.

### names

#### get_subresource

Retrieve the records for the names of a specific product record

#### get_relationship

Retrieve a list of IDs for the names of a specific product record.

#### add_relationship

Set the names of a specific product record

#### update_relationship

Replace the names for a specific product.

#### delete_relationship

Remove the names of a specific product record.

### descriptions

#### get_subresource

Retrieve the records for the descriptions of a specific product record

#### get_relationship

Retrieve a list of IDs for the descriptions of a specific product record.

#### add_relationship

Set the descriptions of a specific product record

#### update_relationship

Replace the descriptions for a specific product.

#### delete_relationship

Remove the descriptions of a specific product record.

### metaDescriptions

#### get_subresource

Retrieve the records for the metaDescriptions of a specific product record

#### get_relationship

Retrieve a list of IDs for the metaDescriptions of a specific product record.

#### add_relationship

Set the metaDescriptions of a specific product record

#### update_relationship

Replace the metaDescriptions for a specific product.

#### delete_relationship

Remove the metaDescriptions of a specific product record.

### metaKeywords

#### get_subresource

Retrieve the records for the metaKeywords of a specific product record

#### get_relationship

Retrieve a list of IDs for the metaKeywords of a specific product record.

#### add_relationship

Set the metaKeywords of a specific product record

#### update_relationship

Replace the metaKeywords for a specific product.

#### delete_relationship

Remove the metaKeywords of a specific product record.

### metaTitles

#### get_subresource

Retrieve the records for the metaTitles of a specific product record

#### get_relationship

Retrieve a list of IDs for the metaTitles of a specific product record.

#### add_relationship

Set the metaTitles of a specific product record

#### update_relationship

Replace the metaTitles for a specific product.

#### delete_relationship

Remove the metaTitles of a specific product record.

### shortDescriptions

#### get_subresource

Retrieve the records for the shortDescriptions of a specific product record

#### get_relationship

Retrieve a list of IDs for the shortDescriptions of a specific product record.

#### add_relationship

Set the shortDescriptions of a specific product record

#### update_relationship

Replace the shortDescriptions for a specific product.

#### delete_relationship

Remove the shortDescriptions of a specific product record.

### slugPrototypes

#### get_subresource

Retrieve the records for the slugPrototypes of a specific product record

#### get_relationship

Retrieve a list of IDs for the slugPrototypes of a specific product record.

#### add_relationship

Set the slugPrototypes of a specific product record

#### update_relationship

Replace the slugPrototypes for a specific product.

#### delete_relationship

Remove the slugPrototypes of a specific product record.

### primaryUnitPrecision

#### get_subresource

Retrieve the record for the primary unit precision of a specific product record

#### get_relationship

Retrieve the ID of the primaryUnitPrecision of a specific product record.

#### update_relationship

Replace the primaryUnitPrecision for a specific product.

### taxCode

#### get_subresource

Retrieve the record for the taxCode of a specific product record

#### get_relationship

Retrieve the ID of the taxCode of a specific product record.

#### update_relationship

Replace the taxCode for a specific product.

### unitPrecisions

#### get_subresource

Retrieve the records for the unitPrecisions of a specific product record

#### get_relationship

Retrieve a list of IDs for the unitPrecisions of a specific product record.

#### add_relationship

Set the unitPrecisions of a specific product record

#### update_relationship

Replace the unitPrecisions for a specific product.

#### delete_relationship

Remove the unit precisions of a specific product record.

### test_variant_field

#### get_subresource

Retrieve the record for the test_variant_field of a specific product record

#### get_relationship

Retrieve the ID of the test_variant_field of a specific product record.

#### update_relationship

Replace the test_variant_field for a specific product.
