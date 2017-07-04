# Oro\Bundle\PricingBundle\Entity\PriceRule

## ACTIONS

### get

Get a price rule by ID.

{@inheritdoc}

### get_list

Get a collection of price rules. A collection may contain all price rules or may be filtered using the standard <a href="https://www.orocommerce.com/documentation/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price rule. The values for `priceList` and `priority` fields are required.

**Limitations:**
* Either `currency` or `currencyExpression` value should be provided.
* Either `quantity` or `quantityExpression` value should be provided.
* Either `productUnit` or `productUnitExpression` value should be provided.

Leaving these pairs of values empty or providing both values is not allowed. 

Please, provide an exactly matching values for the `currency`, `quantity`, and `productUnit` fields. Use the regular expression in the `currencyExpression`, `quantityExpression`, and `productUnitExpression` values.

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

**Limitations:**
* Either `currency` or `currencyExpression` value should be provided.
* Either `quantity` or `quantityExpression` value should be provided.
* Either `productUnit` or `productUnitExpression` value should be provided.

Leaving these pairs of values empty or providing both values is not allowed. 

Please, provide an exactly matching values for the `currency`, `quantity`, and `productUnit` fields. Use the regular expression in the `currencyExpression`, `quantityExpression`, and `productUnitExpression` values.

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

Delete a collection of price rules. A collection may contain all price rules or may be filtered using the standard <a href="https://www.orocommerce.com/documentation/current/dev-guide/integration#filters">filters</a>.

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
