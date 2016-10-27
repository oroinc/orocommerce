Inventory Level API
===================

Table of Contents
-----------------
 - [GET Inventory Levels](#get-inventory-levels)
 - [PATCH Inventory Level](#patch-inventory-level)
 - [POST Inventory Level](#post-inventory-level)
 - [DELETE Inventory Level](#delete-inventory-level)
 - [DELETE Inventory Levels](#delete-inventory-levels)

GET Inventory Levels
==============================

Returns a collection of **Inventory Levels**.

This API can be used to retrieve Inventory Level quantities.

One or more Product SKUs can be provided in order to filter the received data.

One or more Product Unit codes can be provided in order to filter the received data.

Resource URL
------------
`{web_backend_prefix}/api/inventorylevels/`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### product.sku
Product SKU(s).

One or more Product SKUs can be provided as request query.

E.g.: `filter[product.sku]=0RT28` or `filter[product.sku]=0RT28,1AB92`

### productUnitPrecision.unit.code
ProductUnit code(s).

One or more ProductUnit codes can be provided as request query.

E.g.: `filter[productUnitPrecision.unit.code]=item` or `filter[productUnitPrecision.unit.code]=item,set`

### included
Included related resource(s).

One or more resources can be provided in order to include related resources.

E.g.: `include=product` or `include=product,productUnitPrecision`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to view Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/inventorylevels?filter[product.sku]=0RT28,1AB92&filter[productUnitPrecision.unit.code]=item&include=product,productUnitPrecision

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
```json
{
  "data": [
    {
      "type": "inventorylevels",
      "id": "1",
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "productUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "1"
          }
        }
      },
      "attributes": {
        "quantity": "10.0000000000"
      }
    },
    {
      "type": "inventorylevels",
      "id": "3",
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "2"
          }
        },
        "productUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "2"
          }
        }
      },
      "attributes": {
        "quantity": "82.0000000000"
      }
    }
  ],
  "included": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "sku": "0RT28",
        "hasVariants": false,
        "status": "enabled",
        "variantFields": [],
        "createdAt": "2016-07-26T11:01:40Z",
        "updatedAt": "2016-07-26T11:01:43Z"
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
        "primaryUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "1"
          }
        },
        "inventory_status": {
          "data": {
            "type": "prodinventorystatuses",
            "id": "out_of_stock"
          }
        },
        "unitPrecisions": {
          "data": [
            {
              "type": "productunitprecisions",
              "id": "1"
            },
            {
              "type": "productunitprecisions",
              "id": "65"
            }
          ]
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "1",
      "attributes": {
        "precision": 0,
        "conversionRate": 1,
        "sell": true
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "unit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    },
    {
      "type": "products",
      "id": "2",
      "attributes": {
        "sku": "1AB92",
        "hasVariants": false,
        "status": "enabled",
        "variantFields": [],
        "createdAt": "2016-07-26T11:01:40Z",
        "updatedAt": "2016-07-26T11:01:43Z"
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
        "primaryUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "2"
          }
        },
        "inventory_status": {
          "data": {
            "type": "prodinventorystatuses",
            "id": "out_of_stock"
          }
        },
        "unitPrecisions": {
          "data": [
            {
              "type": "productunitprecisions",
              "id": "2"
            },
            {
              "type": "productunitprecisions",
              "id": "66"
            }
          ]
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "2",
      "attributes": {
        "precision": 0,
        "conversionRate": 1,
        "sell": true
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "2"
          }
        },
        "unit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    }
  ]
}
```

PATCH Inventory Level
=====================
Updates a single **Inventory Level**.

This API can be used to update the Inventory Level quantity.

One Product SKU must be provided in order to identify the Inventory Level of the Product.

One Product Unit code can be provided. The default Product Unit is the one of the primary Product Unit Precision of the Product. 

Inventory Level quantity must be provided in order to update it.

Resource URL
------------
`{web_backend_prefix}/api/inventorylevels/{sku}`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### type
Type of the resource.

Must be set to `inventorylevels`.

E.g.: `"type": "inventorylevels"`

### sku
Product SKU.

One Product SKU must be provided in order to identify the Inventory Level of the Product.

The key for the Product SKU is `id` since this is the primary identifier of the Inventory Level.

E.g.: `"id": "0RT28"`

### unit
Product Unit code.

One Product Unit code can be provided. The default Product Unit is the one of the primary Product Unit Precision of the Product.

Product Unit code is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "unit": "item"
}
```

### quantity
Inventory Level quantity.

Inventory Level quantity must be provided in order to update it.

Inventory Level quantity is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "quantity": "17"
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to update Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/inventorylevels/0RT28

Example Request Body
--------------------
```json
{
  "data": {
    "type": "inventorylevels",
    "id": "0RT28",
    "attributes": {
      "quantity": "17",
      "unit": "item"
    }
  }
}
```

Example Response
----------------
```json
{
  "data": {
    "type": "inventorylevels",
    "id": "1",
    "attributes": {
      "quantity": 17
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "productUnitPrecision": {
        "data": {
          "type": "productunitprecisions",
          "id": "1"
        }
      }
    }
  }
}
```

POST Inventory Level
==============================

Creates a single **Inventory Level**.

This API can be used to create a Inventory Level.

One Product SKU must be provided as a Product relationship in order to relate the Inventory Level to a Product.

One Product Unit code can be provided as a Unit relationship in order to relate the Inventory Level to a Product Unit Precision. The default Product Unit is the one of the primary Product Unit Precision of the Product. 

Inventory Level quantity must be provided as an attribute.

Resource URL
------------
`{web_backend_prefix}/api/inventorylevels`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### type
Type of the resource.

Must be set to `inventorylevels`.

E.g.: `"type": "inventorylevels"`

### sku
Product SKU.

One Product SKU must be provided as a Product relationship in order to relate the Inventory Level to a Product.

Product SKU is provided in the `relationships` section.

E.g.:
```json
relationships": {
  "product": {
    "data": {
      "type": "products",
      "id": "0RT28"
    }
  },
}
```

### unit
Product Unit code.

One Product Unit code can be provided as a Unit relationship in order to relate the Inventory Level to a Product Unit Precision. The default Product Unit is the one of the primary Product Unit Precision of the Product.

Product Unit code is provided in the `relationships` section.

E.g.:
```json
relationships": {
  "unit": {
    "data": {
      "type": "productunitprecisions",
      "id": "set"
    }
  },
}
```

### quantity
Inventory Level quantity.

Inventory Level quantity must be provided.

Inventory Level quantity is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "quantity": "17"
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to create Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/inventorylevels

Example Request Body
--------------------
```json
{
  "data": {
    "type": "inventorylevels",
    "attributes": {
      "quantity": "17"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "0RT28"
        }
      },
      "unit": {
        "data": {
          "type": "productunitprecisions",
          "id": "set"
        }
      }
    }
  }
}
```

Example Response
----------------
```json
{
  "data": {
    "type": "inventorylevels",
    "id": "133",
    "attributes": {
      "quantity": 17
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "productUnitPrecision": {
        "data": {
          "type": "productunitprecisions",
          "id": "65"
        }
      }
    }
  }
}
```

DELETE Inventory Level
======================

Deletes a single **Inventory Level**.

This API can be used to delete a Inventory Level.

One Inventory Level id must be provided.

Resource URL
------------
`{web_backend_prefix}/api/inventorylevels/{id}`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### id
Inventory Level id.

One Inventory Level id must be provided in order to identify the Inventory Level.

E.g.: `{web_backend_prefix}/api/inventorylevels/1`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to delete Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/inventorylevels/1

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
No Response Body will be received.

DELETE Inventory Levels
=======================

Deletes a collection of **Inventory Levels**.

This API can be used to delete one or multiple Inventory Level.

One or more Product SKUs can be provided in order to filter the deleted data.

One or more Product Unit codes can be provided in order to filter the deleted data.

Resource URL
------------
`{web_backend_prefix}/api/inventorylevels/`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### product.sku
Product SKU(s).

One or more Product SKUs can be provided as request query.

E.g.: `filter[product.sku]=0RT28` or `filter[product.sku]=0RT28,1AB92`

### productUnitPrecision.unit.code
ProductUnit code(s).

One or more ProductUnit codes can be provided as request query.

E.g.: `filter[productUnitPrecision.unit.code]=item` or `filter[productUnitPrecision.unit.code]=item,set`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to delete Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/inventorylevels?filter[product.sku]=0RT28&filter[productUnitPrecision.unit.code]=item

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
No Response Body will be received.
