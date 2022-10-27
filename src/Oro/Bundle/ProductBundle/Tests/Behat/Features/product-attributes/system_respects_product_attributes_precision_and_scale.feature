@regression
@ticket-BB-20273
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: System respects product attributes precision and scale
  In order to ensure that system respects precision and scale settings and validate value according to them of number type fields
  As an Administrator
  I need to create product attributes with type decimal, float, percent and check how they render and accept the value with big scale

  Scenario: Login and enter to user product attributes page
    Given I login as administrator
    And I go to Products/ Product Attributes

  Scenario Outline: Create Float and Percent Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | <Field Name> |
      | Type       | <Type>       |
    And I click "Continue"
    And I fill form with:
      | Label            | <Label>            |
      | Filterable       | <Filterable>       |
      | Show Grid Filter | <Show Grid Filter> |
      | Auditable        | <Auditable>        |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Field Name                   | Type     | Label                        | Filterable | Show Grid Filter | Auditable |
      | percent_serialized_attribute | Percent  | Percent Serialized Attribute | Yes        | No               | No        |
      | percent_column_attribute     | Percent  | Percent Column Attribute     | Yes        | Yes              | Yes       |
      | float_serialized_attribute   | Float    | Float Serialized Attribute   | Yes        | No               | No        |
      | float_column_attribute       | Float    | Float Column Attribute       | Yes        | Yes              | Yes       |

  Scenario Outline: Create Decimal Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | <Field Name> |
      | Type       | <Type>       |
    And I click "Continue"
    And I fill form with:
      | Label            | <Label>            |
      | Filterable       | <Filterable>       |
      | Show Grid Filter | <Show Grid Filter> |
      | Auditable        | <Auditable>        |
      | Precision        | <Precision>        |
      | Scale            | <Scale>            |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Field Name                            | Type     | Label                                 | Filterable | Show Grid Filter | Auditable | Precision | Scale |
      | decimal_column_attribute              | Decimal  | Decimal Column Attribute              | Yes        | Yes              | Yes       | 10        | 7     |
      | decimal_specific_serialized_attribute | Decimal  | Decimal Specific Serialized Attribute | Yes        | No               | No        | 29        | 20    |
      | decimal_specific_column_attribute     | Decimal  | Decimal Specific Column Attribute     | Yes        | Yes              | Yes       | 29        | 20    |

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And click "Add"
    And fill "Attributes Group Form" with:
      | Attribute Groups Label0      | Scale Settings                                                                                                                                                                                                   |
      | Attribute Groups Visible0    | true                                                                                                                                                                                                             |
      | Attribute Groups Attributes0 | [Percent Serialized Attribute, Percent Column Attribute, Decimal Column Attribute, Decimal Specific Serialized Attribute, Decimal Specific Column Attribute, Float Serialized Attribute, Float Column Attribute] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Check attributes and its cutting in case of big precision overflow
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | <Attribute Name> | <Input Value> |
    And save form
    Then <Attribute Name> field should has <Expected Value> value
    When save and close form
    Then I should see Product with:
      | <Attribute Name> | <Expected Value> |
    Examples:
      | Attribute Name                        | Input Value                      | Expected Value                   |
      | Percent Serialized Attribute          | -0.12345678910111213             | -0.12345678910111%               |
      | Percent Column Attribute              | -0.32145678910111213             | -0.32145678910111%               |
      | Percent Column Attribute              | -4321.12345678910111             | -4,321.1234567891%               |
      | Decimal Specific Serialized Attribute | -1127.1234567891011121314        | -1,127.1234567891011121314       |
      | Decimal Column Attribute              | -117.1234567                     | -117.1234567                     |
      | Decimal Specific Column Attribute     | -1234567.12345678910111213141    | -1,234,567.12345678910111213141  |
      | Float Serialized Attribute            | -0.12345678910111213141516171819 | -0.12345678910111213141516171819 |
      | Float Column Attribute                | -1234.1234567891                 | -1,234.1234567891                |

  Scenario Outline: Check Column Attributes field filter correctly accepts value
    When I go to Products/ Products
    And I filter <Attribute Name> as equals "<Wrong Value>"
    Then there is no records in grid
    And I should see filter hints in grid:
      | <Attribute Name>: equals <Wrong Value Render> |
    And I should see filter <Attribute Name> field value is equal to "<Wrong Value Render>"
    When I filter <Attribute Name> as equals "<Correct Value>"
    Then there is one record in grid
    And I should see filter hints in grid:
      | <Attribute Name>: equals <Correct Value Render> |
    And I should see filter <Attribute Name> field value is equal to "<Correct Value Render>"
    Then I reset "<Attribute Name>" filter
    Examples:
      | Attribute Name           | Wrong Value                     | Wrong Value Render                | Correct Value    | Correct Value Render |
      | Decimal Column Attribute | 1571234.12345678999999900001234 | 1,571,234.12345678999999900001234 | -117.1234567     | -117.1234567         |
      | Float Column Attribute   | 123456789.12345678910111        | 123,456,789.12345678910111        | -1234.1234567891 | -1,234.1234567891    |
      | Percent Column Attribute | -42.12345678910111              | -42.12345678910111%               | -4321.1234567891 | -4,321.1234567891%   |
