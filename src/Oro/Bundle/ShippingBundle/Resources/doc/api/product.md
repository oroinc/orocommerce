# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### create

#### 7. Using product images

Add images definition in the **data** section. Example:

```JSON
    "images": {
      "data": [
        {
          "type": "productimages",
          "id": "product-image-1"
        }
      ]
    }
```

In the **included** section. Example:

```JSON
    {
      "type": "files",
      "id": "file-1",
      "attributes": {
        "mimeType": "image/jpeg",
        "originalFilename": "onedot.jpg",
        "fileSize": 631,
        "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
      }
    },
    {
      "type": "productimagetypes",
      "id": "product-image-type-1",
      "attributes": {
        "productImageTypeType": "main"
      },
      "relationships": {
        "productImage": {
          "data": {
            "type": "productimages",
            "id": "product-image-1"
          }
        }
      }
    },
    {
      "type": "productimagetypes",
      "id": "product-image-type-2",
      "attributes": {
        "productImageTypeType": "listing"
      },
      "relationships": {
        "productImage": {
          "data": {
            "type": "productimages",
            "id": "product-image-1"
          }
        }
      }
    },
    {
      "type": "productimages",
      "id": "product-image-1",
      "relationships": {
        "image": {
          "data": {
            "type": "files",
            "id": "file-1"
          }
        },
        "types": {
          "data": [
            {
              "type": "productimagetypes",
              "id": "product-image-type-1"
            },
            {
              "type": "productimagetypes",
              "id": "product-image-type-2"
            }
          ]
        },
        "product": {
          "data": {
            "type": "products",
            "id": "product-id"
          }
        }
      }
    }
```

The example above also creates product image mandatory subresources : files and types.
The type attribute of the product image type model ("productImageTypeType") should be a valid type
of image defined in themes  and it is not directly handled by the API.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "attributes": {
      "sku": "test-api-1",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false,
      "availability_date": "2018-01-01"
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
            "type": "productnames",
            "id": "names-1"
          },
          {
            "type": "productnames",
            "id": "names-2"
          }
        ]
      },
      "shortDescriptions": {
        "data": [
          {
            "type": "productshortdescriptions",
            "id": "short-descriptions-1"
          },
          {
            "type": "productshortdescriptions",
            "id": "short-descriptions-2"
          }
        ]
      },
      "descriptions": {
        "data": [
          {
            "type": "productdescriptions",
            "id": "descriptions-1"
          },
          {
            "type": "productdescriptions",
            "id": "descriptions-2"
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
      "primaryUnitPrecision": {
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
      "productShippingOptions":{
        "data":[
          {
            "type":"productshippingoptions",
            "id":"product-shipping-options-1"
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
      "highlightLowInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "low1abcd"
        }
      },
      "lowInventoryThreshold": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "low2abcd"
        }
      },
      "isUpcoming": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "product-is-upcoming"
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
  "included": [
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
      "id": "low1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "low2abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "41",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "product-is-upcoming",
      "attributes": {
        "fallback": null,
        "scalarValue": "1",
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
      "type": "productnames",
      "id": "names-1",
      "attributes": {
        "fallback": null,
        "string": "Test product"
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productnames",
      "id": "names-2",
      "attributes": {
        "fallback": null,
        "string": "Product in Spanish"
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "productshortdescriptions",
      "id": "short-descriptions-1",
      "attributes": {
        "fallback": null,
        "text": "Test product short description"
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productshortdescriptions",
      "id": "short-descriptions-2",
      "attributes": {
        "fallback": null,
        "text": "Product Short Description in Spanish"
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "productdescriptions",
      "id": "descriptions-1",
      "attributes": {
        "fallback": null,
        "wysiwyg": {
          "value": "Test product description",
          "style": null,
          "properties": null
        }
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productdescriptions",
      "id": "descriptions-2",
      "attributes": {
        "fallback": null,
        "wysiwyg": {
          "value": "Product Short Description in Spanish",
          "style": null,
          "properties": null
        }
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
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
    },
    {
      "type":"productshippingoptions",
      "id":"product-shipping-options-1",
      "attributes":{
        "weightValue":10,
        "dimensionsLength":0.6,
        "dimensionsWidth":0.8,
        "dimensionsHeight":0.1
      },
      "relationships":{
        "productUnit":{
          "data":{
            "type":"productunits",
            "id":"set"
          }
        },
        "weightUnit":{
          "data":{
            "type":"weightunits",
            "id":"kg"
          }
        },
        "dimensionsUnit":{
          "data":{
            "type":"lengthunits",
            "id":"m"
          }
        }
      }
    }
  ]
}
```
{@/request}

### update

#### 2. Updating "localizedfallbackvalues" (localized fields) and "entityfieldfallbackvalues" (options with fallbacks) types

**Important** - When you want to update existing related entities, it can only be done by using the
**included** section, the same way it is used in the create section. What is important to mention is:

* you must use the real ID of the related entity that you want to update, example:

> - **data** section

```JSON
        "manageInventory": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "id": "466"
          }
        }
```

> - **included** section

```JSON
        {
          "meta": {
             "update": true
          },
          "type": "localizedfallbackvalues",
          "id": "807",
          "attributes": {
            "fallback": null,
            "string": "Test value - updated",
            "text": null
          }
        }
```

* use the **update** flag to specify it is an update on an existing entity, otherwise it will attempt
  the creation of a new entity of that type

```JSON
          "meta": {
             "update": true
          }
```

**Important** when wanting to update the current entity by modifying a relation, which is actually
a `to-many` relationship with another entity, you must specify all of the entities from that list.
If you don't do that, the system will set on that relation the input that has been received. So for
example if I have the "names" relation which holds a collection of "localizedfallbackvalues" type,
with 8 entities, and I specify only 2 of these entities in the input, then in the database I will
have only those 2 saved and all the other (6 entities) will be removed. Example for updating the
"names" relation with modifying the text for a specific localization:

> - in the **data** section:

```JSON
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
```

> - in the **included** section:

```JSON
    {
      "meta": {
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "807",
      "attributes": {
        "fallback": null,
        "string": "Test value - updated",
        "text": null
      }
    }
```

{@request:json_api}
Example:

```JSON
{
  "data": {
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
            "type": "productnames",
            "id": "807"
          }
        ]
      },
      "slugPrototypes": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "907"
          }
        ]
      },
      "primaryUnitPrecision": {
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
      },
      "productShippingOptions":{
        "data":[
          {
            "type":"productshippingoptions",
            "id":"product-shipping-options-1"
          }
        ]
      }
    }
  },
  "included": [
    {
      "meta": {
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
      "meta": {
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "907",
      "attributes": {
        "fallback": null,
        "string": "test-prod-slug-updated",
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
    },
    {
      "type": "productshippingoptions",
      "id": "product-shipping-options-1",
      "attributes": {
        "weightValue": 10,
        "dimensionsLength": 0.6,
        "dimensionsWidth": 0.8,
        "dimensionsHeight": 0.1
      },
      "relationships": {
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
        }
      }
    }
  ]
}
```
{@/request}

## FIELDS

### productShippingOptions

The product shipping options for the product.

## SUBRESOURCES

### productShippingOptions

#### get_subresource

Retrieve the records for the product shipping options of a specific product record.

#### get_relationship

Retrieve a list of IDs for the product shipping options of a specific product record.

#### add_relationship

Set the product shipping options of a specific product record.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}
```

{@/request}

#### update_relationship

Replace the product shipping options for a specific product.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}
```

{@/request}

#### delete_relationship

Remove the product shipping options of a specific product record.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}
```

{@/request}
