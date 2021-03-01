# Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility

## ACTIONS

### get

Retrieve a specific customer product visibility record.

{@inheritdoc}

### get_list

Retrieve a collection of customer product visibility records.

{@inheritdoc}

### create

Create a new customer product visibility record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customerproductvisibilities",    
    "attributes": { 
      "visibility": "visible"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific customer product visibility record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customerproductvisibilities", 
    "id": "1-1",   
    "attributes": { 
      "visibility": "visible"
    }
  }
}
```
{@/request}

### delete

Delete a specific customer product visibility record.

{@inheritdoc}

### delete_list

Delete a collection of customer product visibility records.

{@inheritdoc}

## FIELDS

### visibility

The visibility level. Possible values: `visible`, `hidden`, `category`, `customer_group`, `current_product`.

The `category` level is available only if a product is assigned to a category.

#### create, update

{@inheritdoc}

**The required field.**

### product

The product this visibility rule is intended for.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### customer

The customer this visibility rule belongs to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
