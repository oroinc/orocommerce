# Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct

## ACTIONS

### get

Retrieve a relationship between the main product and one of its related products.

**Definition:** In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

### get_list

Retrieve collections of related product relationships. An item in the collection is a relationship between the main product and one of its related products.

**Definition:** In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

### create

Create a new relationship between a product and one of its related products.

The created record is returned in the response.

**Definition:** In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

#### Validation

During the POST request, the following validation constraints are checked:

* Related Products feature must be enabled.
* Limit of number of Related Products for given Product cannot be exceeded.
* It is not possible to create a relation between the same products. For example, creating a relation between ProductA (as the main product) and ProductA (as the related product) will cause an `HTTP 400 Bad Request` response.
* It is not possible to re-create an already existing relationship. When relationship from ProductA (main product) to ProductB (related item) already exists, and you send the repetitive request to create this relationship, such request will cause an `HTTP 400 Bad Request` response.

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "relatedproducts",
      "relationships": {
         "product": {
            "data": {
               "type": "products",
               "id": "1"
            }
         },
         "relatedItem": {
            "data": {
               "type": "products",
               "id": "2"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a relationship between the main product and one of its related products.

**Definition:** In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

### delete_list

Delete a collection of relationships between the products and their related products.

**Definition:** In the product details, related products (like accessories, additional services, and similar products) may be shown to the buyer and sales manager in the Related Items section, alongside with the up-sell and cross-sell products.

## FIELDS

### product

The main product in the relationship.

#### create

{@inheritdoc}

**The required field.**

### relatedItem

The product that is related to the main product in the relationship.

#### create

{@inheritdoc}

**The required field.**

## SUBRESOURCES

### product

#### get_subresource

Get complete product information about the main product in the relationship.

#### get_relationship

Get the identifier of the main product in the relationship.

### relatedItem

#### get_subresource

Get complete product information about the product that is related to the main product in the relationship.

#### get_relationship

Get the identifier of the product that is related to the main product in the relationship.
