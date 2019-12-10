# Oro\Bundle\OrderBundle\Entity\OrderLineItem

## FIELDS

### unitPriceIncludingTax

Unit Price (Including Tax).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### unitPriceExcludingTax

Unit Price (Excluding Tax).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### unitPriceTaxAmount

Unit Price (Tax Amount).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### rowTotalIncludingTax

Row Total (Including Tax).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### rowTotalExcludingTax

Row Total (Excluding Tax).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### rowTotalTaxAmount

Row Total (Tax Amount).

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### taxes

An array of the taxes that are applied to the order line item.

Each element of the array is an object with the following properties:

**tax** is a string that contains the tax code.

**rate** is a string that contains the tax rate in percents.

**taxableAmount** is a string that contains the monetary value of the taxable amount.

**taxAmount** is a string that contains the monetary value of the tax.

**currency** is a string that contains the currency of the tax amounts.

Example of data: **\[{"tax": "LOS_ANGELES_COUNTY_SALES_TAX", "rate": "0.09", "taxableAmount": "319.96", "taxAmount": "28.8", "currency": "USD"}\]**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
