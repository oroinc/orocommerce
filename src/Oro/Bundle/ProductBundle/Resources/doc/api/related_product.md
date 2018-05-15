# Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct

## ACTIONS

### get

Retrieve a relationship (identified by id) between the main product and one of its related products.

<b>Definition:</b> In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

### get_list

Retrieve collections of related product relationships. An item in the collection is a relationship between the main product and one of its related products. A collection may contain all related product links or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

<b>Definition:</b> In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

### create

Create a new relationship between a product and one of its related products. A newly created relationship record is returned in the response.

<b>Definition:</b> In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

#### Validation

During the POST request, the following validation constraints are checked:

* Related Products feature must be enabled (see `Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider::isEnabled()` for details).

* Limit of number of Related Products for given Product cannot be exceeded (see `Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider::getLimit()` for details).

* It is not possible to create a relation between the same products. For example, creating a relation between ProductA (as the main product) and ProductA (as the related product) will cause an `HTTP 400 Bad Request` response.

* It is not possible to re-create an already existing relationship. When relationship from ProductA (main product) to ProductB (related item) already exists, and you send the repetitive request to create this relationship, such request will cause an `HTTP 400 Bad Request` response.

{@request:json_api}

Example:

`</admin/api/relatedproducts>`

```JSON
{  
   "data":{  
      "type":"relatedproducts",
      "relationships":{  
         "product":{  
            "data":{  
               "type":"products",
               "id":"1"
            }
         },
         "relatedItem":{  
            "data":{  
               "type":"products",
               "id":"2"
            }
         }
      }
   }
}

```
{@/request}

### delete

Delete a relationship (identified by id) between the main product and one of its related products.

<b>Definition:</b> In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

{@inheritdoc}

### delete_list

Delete a collection of relationships between the products and their related products. A collection may contain all related product links or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

<b>Definition:</b> In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

## FIELDS

### product

The main product in the relationship.

#### create

{@inheritdoc}

**Required field**

### relatedItem

The product that is related to the main product in the relationship.

#### create

{@inheritdoc}

**Required field**

## SUBRESOURCES

### product

#### get_subresource

Get complete product information about the main product in the relationship. The relationship is identified by id. 

#### get_relationship

Get the identifier of the main product in the relationship. The relationship is identified by id.

### relatedItem

#### get_subresource

Get complete product information about the product that is related to the main product in the relationship. The relationship is identified by id. 

#### get_relationship

Get the identifier of the product that is related to the main product in the relationship. The relationship is identified by id. 
