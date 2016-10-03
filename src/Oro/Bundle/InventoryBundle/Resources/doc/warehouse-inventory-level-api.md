Warehouse Inventory Level API
=============================

Table of Contents
-----------------
 - [GET Warehouse Inventory Levels](#get-warehouse-inventory-levels)
 - [PATCH Warehouse Inventory Level](#patch-warehouse-inventory-level)
 - [POST Warehouse Inventory Level](#post-warehouse-inventory-level)
 - [DELETE Warehouse Inventory Level](#delete-warehouse-inventory-level)
 - [DELETE Warehouse Inventory Levels](#delete-warehouse-inventory-levels)

GET Warehouse Inventory Levels
==============================

Returns a collection of **Warehouse Inventory Levels**.

This API can be used to retrieve Warehouse Inventory Level quantities.

One or more Product SKUs can be provided in order to filter the received data.

One or more Warehouse ids can be provided in order to filter the received data.

One or more Product Unit codes can be provided in order to filter the received data.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/`

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

### warehouse
Warehouse ID(s).

One or more Warehouse ids can be provided as request query.

E.g.: `filter[warehouse]=1` or `filter[warehouse]=1,2`

### included
Included related resource(s).

One or more resources can be provided in order to include related resources.

E.g.: `include=product` or `include=product,productUnitPrecision`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to view Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels?filter[product.sku]=0RT28,1AB92&filter[productUnitPrecision.unit.code]=item&filter[warehouse]=1,2&include=product,productUnitPrecision

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
```json
{
  "data": [
    {
      "type": "warehouseinventorylevels",
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
        },
        "warehouse": {
          "data": {
            "type": "warehouses",
            "id": "1"
          }
        }
      },
      "attributes": {
        "quantity": "10.0000000000"
      }
    },
    {
      "type": "warehouseinventorylevels",
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
        },
        "warehouse": {
          "data": {
            "type": "warehouses",
            "id": "1"
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

PATCH Warehouse Inventory Level
===============================
Updates a single **Warehouse Inventory Level**.

This API can be used to update the Warehouse Inventory Level quantity.

One Product SKU must be provided in order to identify the Warehouse Inventory Level of the Product.

One Warehouse ID must be provided if there are multiple Warehouses in the system.

One Product Unit code can be provided. The default Product Unit is the one of the primary Product Unit Precision of the Product. 

Warehouse Inventory Level quantity must be provided in order to update it.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/{sku}`

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

Must be set to `warehouseinventorylevels`.

E.g.: `"type": "warehouseinventorylevels"`

### sku
Product SKU.

One Product SKU must be provided in order to identify the Warehouse Inventory Level of the Product.

The key for the Product SKU is `id` since this is the primary identifier of the Warehouse Inventory Level.

E.g.: `"id": "0RT28"`

### warehouse
Warehouse ID.

One Warehouse ID must be provided if there are multiple Warehouses in the system.

Warehouse ID is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "warehouse": "1"
}
```

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
Warehouse Inventory Level quantity.

Warehouse Inventory Level quantity must be provided in order to update it.

Warehouse Inventory Level quantity is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "quantity": "17"
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to update Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels/0RT28

Example Request Body
--------------------
```json
{
  "data": {
    "type": "warehouseinventorylevels",
    "id": "0RT28",
    "attributes": {
      "quantity": "17",
      "warehouse": "1",
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
    "type": "warehouseinventorylevels",
    "id": "1",
    "attributes": {
      "quantity": 17
    },
    "relationships": {
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "1"
        }
      },
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

POST Warehouse Inventory Level
==============================

Creates a single **Warehouse Inventory Level**.

This API can be used to create a Warehouse Inventory Level.

One Product SKU must be provided as a Product relationship in order to relate the Warehouse Inventory Level to a Product.

One Warehouse ID must be provided as a Warehouse relationship if there are multiple Warehouses in the system in order to relate the Warehouse Inventory Level to a Warehouse.

One Product Unit code can be provided as a Unit relationship in order to relate the Warehouse Inventory Level to a Product Unit Precision. The default Product Unit is the one of the primary Product Unit Precision of the Product. 

Warehouse Inventory Level quantity must be provided as an attribute.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels`

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

Must be set to `warehouseinventorylevels`.

E.g.: `"type": "warehouseinventorylevels"`

### sku
Product SKU.

One Product SKU must be provided as a Product relationship in order to relate the Warehouse Inventory Level to a Product.

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

### warehouse
Warehouse ID.

One Warehouse ID must be provided as a Warehouse relationship if there are multiple Warehouses in the system in order to relate the Warehouse Inventory Level to a Warehouse.

Warehouse ID is provided in the `relationships` section. The Type of the resource must be provided.

E.g.:
```json
relationships": {
  "warehouse": {
    "data": {
      "type": "warehouses",
      "id": "2"
    }
  },
}
```

### unit
Product Unit code.

One Product Unit code can be provided as a Unit relationship in order to relate the Warehouse Inventory Level to a Product Unit Precision. The default Product Unit is the one of the primary Product Unit Precision of the Product.

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
Warehouse Inventory Level quantity.

Warehouse Inventory Level quantity must be provided.

Warehouse Inventory Level quantity is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "quantity": "17"
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to create Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels

Example Request Body
--------------------
```json
{
  "data": {
    "type": "warehouseinventorylevels",
    "attributes": {
      "quantity": "17"
    },
    "relationships": {
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "2"
        }
      },
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
    "type": "warehouseinventorylevels",
    "id": "133",
    "attributes": {
      "quantity": 17
    },
    "relationships": {
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "2"
        }
      },
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

DELETE Warehouse Inventory Level
================================

Deletes a single **Warehouse Inventory Level**.

This API can be used to delete a Warehouse Inventory Level.

One Warehouse Inventory Level id must be provided.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/{id}`

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
Warehouse Inventory Level id.

One Warehouse Inventory Level id must be provided in order to identify the Warehouse Inventory Level.

E.g.: `{web_backend_prefix}/api/warehouseinventorylevels/1`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to delete Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels/1

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
No Response Body will be received.

DELETE Warehouse Inventory Levels
=================================

Deletes a collection of **Warehouse Inventory Levels**.

This API can be used to delete one or multiple Warehouse Inventory Level.

One or more Product SKUs can be provided in order to filter the deleted data.

One or more Warehouse ids can be provided in order to filter the deleted data.

One or more Product Unit codes can be provided in order to filter the deleted data.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/`

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

### warehouse
Warehouse ID(s).

One or more Warehouse ids can be provided as request query.

E.g.: `filter[warehouse]=1` or `filter[warehouse]=1,2`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to delete Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels?filter[product.sku]=0RT28&filter[productUnitPrecision.unit.code]=item&filter[warehouse]=1

Example Request Body
--------------------
No Request Body is required.

Example Response
----------------
No Response Body will be received.
