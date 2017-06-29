# Oro\Bundle\PricingBundle\Entity\PriceRule

## ACTIONS

### get

Get one PriceRule entity

### get_list

Get a collection of PriceRule entities

### create

Create a new PriceRule entity. Required fields are: `priceList`, `priority`. 
One of these fields should be blank but not both of them: `currency` or `currencyExpression`, 
`quantity` or `quantityExpression`, `productUnit` or `productUnitExpression`. It is allowed to set
an exact value or expression for these fields.
Example:
```
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

### update

Update one PriceRule entity. One of the fields: `currency` or `currencyExpression`,
`quantity` or `quantityExpression`, `productUnit` or `productUnitExpression` should be blank but
not both of them. `priceList` field is not allowed to be updated, you should delete wrong price rule
and create a new one.
Example:
```
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

### delete_list

Delete a collection of PriceRule entities

## FIELDS

### priceList

#### create

**The required field**

### currency

#### create, update

**One of the fields: `currency`, `currencyExpression` should be blank**

### currencyExpression

#### create, update

**One of the fields: `currency`, `currencyExpression` should be blank**

### quantity

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### quantityExpression

#### create, update

**One of the fields: `quantity`, `quantityExpression` should be blank**

### productUnit

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### productUnitExpression

#### create, update

**One of the fields: `productUnit`, `productUnitExpression` should be blank**

### priority

#### create

**The required field**

## SUBRESOURCES

### priceList

#### get_subresource

Get full information about the rule price list

### productUnit

#### get_subresource

Get full information about the rule product unit
