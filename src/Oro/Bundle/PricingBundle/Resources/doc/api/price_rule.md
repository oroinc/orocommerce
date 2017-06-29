# Oro\Bundle\PricingBundle\Entity\PriceRule

## ACTIONS

### get

Get one PriceRule entity

{@inheritdoc}

### get_list

Get a collection of PriceRule entities

{@inheritdoc}

### create

Create a new PriceRule entity. Required fields are: `priceList`, `priority`. 
One of these fields should be blank but not both of them: `currency` or `currencyExpression`, 
`quantity` or `quantityExpression`, `productUnit` or `productUnitExpression`. It is allowed to set
an exact value or expression for these fields.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricerules>`

```JSON
{
    "data": {
        "type": "pricerules",
        "attributes": {
            "currency": "USD",
            "currencyExpression": "",
            "quantity": "2",
            "quantityExpression": "",
            "productUnitExpression": "",
            "ruleCondition": "product.category.id > 0",
            "rule": "pricelist[0].prices.value * 0.9",
            "priority": "1"
        },
        "relationships": {
            "productUnit": {
                "data": {"type": "productunits", "id": "item"}
            },
            "priceList": {
                 "data": {"type": "pricelists", "id": "1"}
            }
        }
    }
}
```
{@/request}

### update

Update one PriceRule entity. One of the fields: `currency` or `currencyExpression`,
`quantity` or `quantityExpression`, `productUnit` or `productUnitExpression` should be blank but
not both of them. `priceList` field is not allowed to be updated, you should delete wrong price rule
and create a new one.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricerules/1>`

```JSON
{
    "data": {
        "id": "1",
        "type": "pricerules",
        "attributes": {
            "currency": "",
            "currencyExpression": "pricelist[0].prices.currency",
            "quantity": null,
            "quantityExpression": "pricelist[0].prices.quantity + 2",
            "productUnitExpression": "pricelist[0].prices.unit",
            "priority": "5"
        },
        "relationships": {
            "productUnit": {
                "data": {"type": "productunits", "id": "set"}
            }
        }
    }
}
```

### delete

Delete one PriceRule entity

{@inheritdoc}

### delete_list

Delete a collection of PriceRule entities

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### priceList

Price list that is attached to the price rule

#### create

**The required field**

### currency

Defines which product price currency in a price list would be affected by the rule

#### create, update

**One of the fields: `currency`, `currencyExpression` should be blank**

### currencyExpression

Defines an expression to calculate a product price currency value to which apply the rule

#### create, update

**One of the fields: `currency`, `currencyExpression` should be blank**

### quantity

Defines a product quantity to which apply the rule

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### quantityExpression

Defines an expression to calculate a product quantity value to which apply the rule

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### productUnit

Defines a product unit to which apply the rule

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### productUnitExpression

Defines an expression to calculate a product unit code to which apply the rule

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### priority

Price Rule priority in a price list

#### create

**The required field**

### ruleCondition

Condition to apply the rule

### rule

Rule for calculating product price

## SUBRESOURCES

### priceList

#### get_subresource

Get full information about the rule price list

#### get_relationship

Get price list ID for a specific price rule

### productUnit

#### get_subresource

Get full information about the rule product unit

#### get_relationship

Get product unit code for a specific price rule

#### update_relationship

Update product unit for a specific price rule
