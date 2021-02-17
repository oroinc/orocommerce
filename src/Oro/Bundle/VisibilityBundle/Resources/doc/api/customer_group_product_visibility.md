# Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility

## ACTIONS

### get

Retrieve a specific customer group product visibility record.

{@inheritdoc}

### get_list

Retrieve a collection of customer group product visibility records.

{@inheritdoc}

### create

Create a new customer group product visibility record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customergroupproductvisibilities",    
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
      "customerGroup": {
        "data": {
          "type": "customergroups",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific customer group product visibility record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customergroupproductvisibilities", 
    "id": "1-1",   
    "attributes": { 
      "visibility": "visible"
    }
  }
}
```
{@/request}

### delete

Delete a specific customer group product visibility record.

{@inheritdoc}

### delete_list

Delete a collection of customer group product visibility records.

{@inheritdoc}

## FIELDS

### visibility

The visibility level. Possible values: `visible`, `hidden`, `category`, `current_product`.

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

### customerGroup

The customer group this visibility rule belongs to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
