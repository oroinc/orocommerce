# Oro\Bundle\OrderBundle\Entity\OrderLineItem

## FIELDS

### unitPriceIncludingTax

Unit Price (Including Tax).

### unitPriceExcludingTax

Unit Price (Excluding Tax).

### unitPriceTaxAmount

Unit Price (Tax Amount).

### rowTotalIncludingTax

Row Total (Including Tax).

### rowTotalExcludingTax

Row Total (Excluding Tax).

### rowTotalTaxAmount

Row Total (Tax Amount).

### taxes

An array of the taxes that are applied to the order line item.

Each element of the array is an object with the following properties:

**tax** is a string contains the tax code.

**rate** is a string contains the tax rate in percents.

**taxableAmount** is a string contains the monetary value of the taxable amount.

**taxAmount** is a string contains the monetary value of the tax.

**currency** is a string contains the currency of the tax amounts.

Example of data: **\[{"tax": "LOS_ANGELES_COUNTY_SALES_TAX", "rate": "0.09", "taxableAmount": "319.96", "taxAmount": "28.8", "currency": "USD"}\]**
