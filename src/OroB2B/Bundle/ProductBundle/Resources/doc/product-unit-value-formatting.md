Product Unit Value Formatting
==================

Table of Contents
-----------------
 - [Formats source](#format-source)
 - [PHP Product Unit Value Formatter](#php-product-unit-value-formatter)
    - [Methods and examples of usage](#methods-and-examples-of-usage)
      - [format](#format)
      - [formatShort](#formatShort)
   - [Twig](#twig)
    - [Filters](#filters)
      - [orob2b_format_product_unit_value](#orob2b_format_product_unit_value)
      - [orob2b_format_short_product_unit_value](#orob2b_format_short_product_unit_value)

Formats source
================
Product Unit formats may be found in messages.<locale>.yml.

Example of format configuration for en_US:

```yaml
product_unit.kg:
    label:
        full: kilogramm
        short: kg
    value:
        full: '{0} none|{1} one kilogram|]1,Inf] %count% kilograms'
        short: '{0} none|{1} one kg|]1,Inf] %count% kg'
```

Possible format placeholders:

* *count* - product unit value

PHP Name Formatter
====================

**Class:** OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter

**Service id:** orob2b_product.formatter.product_unit_value

Formats product unit value based on given product unit.

Methods and examples of usage
-----------------------------

### format

string *public* *format*(float|integer *value*, ProductUnit *unit*)

This method can be used to format value that is type of float or integer.

To format value *unit* parameters must be passed and it must be instance of ProductUnit class.

Format:

```yaml
product_unit.kg:
    label:
        full: kilogramm
        short: kg
    value:
        full: '{0} none|{1} one kilogram|]1,Inf] %count% kilograms'
        short: '{0} none|{1} one kg|]1,Inf] %count% kg'
```

Code:

```php
// $unit implements ProductUnit
$unit->setCode('kg');

$formatter = $container->get('orob2b_product.formatter.product_unit_value');
echo $formatter->format(5, $unit);
```

Outputs:

```
5 kilograms
```


### formatShort

string *public* *formatShort*(float|integer *value*, ProductUnit *unit*)

This method can be used to format value that is type of float or integer.

To format value *unit* parameters must be passed and it must be instance of ProductUnit class.

Format:

```yaml
product_unit.kg:
    label:
        full: kilogramm
        short: kg
    value:
        full: '{0} none|{1} one kilogram|]1,Inf] %count% kilograms'
        short: '{0} none|{1} one kg|]1,Inf] %count% kg'
```

Code:

```php
// $unit implements ProductUnit
$unit->setCode('kg');

$formatter = $container->get('orob2b_product.formatter.product_unit_value');
echo $formatter->formatShort(5, $unit);
```

Outputs:

```
5 kg
```

Twig
====

Filters
-------

### orob2b_format_product_unit_value

This filter use *format* method from product unit value formatter, and has same logic.

```
{{ value|orob2b_format_product_unit_value(unit) }}
```


### orob2b_format_short_product_unit_value

This filter use *formatShort* method from product unit value formatter, and has same logic.

```
{{ value|orob2b_format_short_product_unit_value(unit) }}
```
