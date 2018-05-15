# Oro\Bundle\PricingBundle\Entity\PriceRule

## ACTIONS

### get

Get a price rule by ID.

{@inheritdoc}

### get_list

Get a collection of price rules. A collection may contain all price rules or may be filtered using the standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price rule. The values for `priceList` and `priority` fields are required.

Information about the currency, quantity, and product unit is required for price rule creation. 
Currency, quantity, and product unit may be defined either by the value or via regular expression the value matches.
The values should be provided in the `currency`, `quantity`, and `productUnit` fields (or the plain fields).
The regular expressions should be provided in the `currencyExpression`, `quantityExpression`, and `productUnitExpression` (or the regular expression fields).

The plain and regular expression fields are mutually exclusive. Do not leave both parameters unset and do not set both parameters in the same request. 

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

Update a price rule identified by ID. 

Information about the currency, quantity, and product unit is required for price rule creation. 
Currency, quantity, and product unit may be defined either by the value or via regular expression the value matches.
The values should be provided in the `currency`, `quantity`, and `productUnit` fields (or the plain fields).
The regular expressions should be provided in the `currencyExpression`, `quantityExpression`, and `productUnitExpression` (or the regular expression fields).

The plain and regular expression fields are mutually exclusive. Do not leave both parameters unset and do not set both parameters in the same request. 

The `priceList` value is not allowed to be updated. 

To modify the relationship with the price list, delete the incorrect price rule and create a new one including the correct price list relationship.

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

Delete a price rule identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price rules. A collection may contain all price rules or may be filtered using the standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

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

Defines an expression to calculate a product price currency value to which the rule applies

#### create, update

**One of the fields: `currency`, `currencyExpression` should be blank**

### quantity

Defines a product quantity to which the rule applies

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### quantityExpression

Defines an expression to calculate a product quantity value to which the rule applies

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### productUnit

Defines a product unit to which the rule applies

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### productUnitExpression

Defines an expression to calculate a product unit code to which the price rule applies

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### priority

Price Rule priority in a price list

#### create

**The required field**

### ruleCondition

Condition that should match for the rule to apply
 
### rule

Rule that is used for calculating product price

## SUBRESOURCES

### priceList

#### get_subresource

Get complete information about the price list the price rule applies for

#### get_relationship

Get price list ID for a specific price rule

### productUnit

#### get_subresource

Get complete information about the product unit the price rule applies for

#### get_relationship

Get product unit code for a specific price rule

#### update_relationship

Update product unit for a specific price rule
