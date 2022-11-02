# Oro\Bundle\PricingBundle\Entity\PriceRule

## ACTIONS

### get

Retrieve a specific price rule record.

{@inheritdoc}

### get_list

Retrieve a collection of price rule records.

{@inheritdoc}

### create

Create a new price rule.

The created record is returned in the response.

{@inheritdoc}

Information about the currency, quantity, and product unit is required for price rule creation. 
Currency, quantity, and product unit may be defined either by the value or via regular expression the value matches.
The values should be provided in the **currency**, **quantity**, and **productUnit** fields (or the plain fields).
The regular expressions should be provided in the **currencyExpression**, **quantityExpression**, and **productUnitExpression** (or the regular expression fields).

The plain and regular expression fields are mutually exclusive. Do not leave both parameters unset and do not set both parameters in the same request. 

{@request:json_api}
Example:

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

Edit a specific price rule record.

The updated record is returned in the response.

{@inheritdoc}

Information about the currency, quantity, and product unit is required for price rule creation. 
Currency, quantity, and product unit may be defined either by the value or via regular expression the value matches.
The values should be provided in the **currency**, **quantity**, and **productUnit** fields (or the plain fields).
The regular expressions should be provided in the **currencyExpression**, **quantityExpression**, and **productUnitExpression** (or the regular expression fields).

The plain and regular expression fields are mutually exclusive. Do not leave both parameters unset and do not set both parameters in the same request. 

The **priceList** association is not allowed to be updated. 

To modify the relationship with the price list, delete the incorrect price rule and create a new one including the correct price list relationship.

{@request:json_api}
Example:

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
{@/request}

### delete

Delete a specific price rule record.

{@inheritdoc}

### delete_list

Delete a collection of price rule records.

{@inheritdoc}

## FIELDS

### priceList

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **currency**, **currencyExpression** should be blank.

### currencyExpression

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **currency**, **currencyExpression** should be blank.

### quantity

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **quantity**, **quantityExpression** should be blank.

### quantityExpression

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **quantity**, **quantityExpression** should be blank.

### productUnit

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **productUnit**, **productUnitExpression** should be blank.

### productUnitExpression

#### create, update

{@inheritdoc}

**Note:**
One of the fields: **productUnit**, **productUnitExpression** should be blank.

### priority

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### priceList

#### get_subresource

Get complete information about the price list the price rule applies for.

#### get_relationship

Get price list ID for a specific price rule.

### productUnit

#### get_subresource

Get complete information about the product unit the price rule applies for.

#### get_relationship

Get product unit code for a specific price rule.

#### update_relationship

Update product unit for a specific price rule.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}
