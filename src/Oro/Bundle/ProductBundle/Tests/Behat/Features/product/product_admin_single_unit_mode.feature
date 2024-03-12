@ticket-BB-12801
@ticket-BB-22546
@fixture-OroProductBundle:ProductKitsExportFixture.yml

Feature: Product admin single unit mode
  In order to successfully use single unit mode in the system
  As an administrator
  I need to be able to enable single unit mode and I need to see only one option for primary unit on product create page

  Scenario: Enable Single Unit mode
    Given login as administrator
    And go to System/ Configuration
    And follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And uncheck "Use default" for "Default Primary Unit" field
    And I select "each" from "Default Primary Unit"
    And I save setting

  Scenario: Create new simple product with Single Unit mode
    Given go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    When click "Continue"
    Then I should see "each" for "Unit Of Quantity" select
    And I should not see "item" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    When click "AddPrice"
    Then I should see "each" for "Price Unit" select
    And I click "Cancel"

  Scenario: Edit existing simple product with Single Unit mode
    Given go to Products/ Products
    When I click Edit "PSKU1" in grid
    Then I should see "set" for "Unit Of Quantity" select
    And I should see "each" for "Unit Of Quantity" select
    And I should not see "item" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    When click "AddPrice"
    Then I should see "set" for "Price Unit" select

  Scenario: Check changing Unit Of Quantity with Single Unit mode
    When fill "Create Product Form" with:
      | Unit Of Quantity | each |
    Then I should see "set - removed" for "Price Unit" select
    And I should see "each" for "Price Unit" select
    And I should not see "item" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    And I click "Cancel"

  Scenario: Edit existing product kit with Single Unit mode
    Given I click Edit "PSKU_KIT1" in grid
    Then I should see "set" for "Unit Of Quantity" select
    And I should see "each" for "Unit Of Quantity" select
    And I should not see "item" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    When I click "Kit Items"
    Then I should see "set" for "Kit Item 1 Product Unit" select
    And I should see "each" for "Kit Item 1 Product Unit" select
    And I should not see "item" for "Kit Item 1 Product Unit" select
    And I should not see "hour" for "Kit Item 1 Product Unit" select
    When click "AddPrice"
    Then I should see "set" for "Price Unit" select
    And I click "Cancel"

  Scenario: Create new product kit with Single Unit mode
    Given click "Create Product"
    And fill form with:
      | Type | Kit |
    When click "Continue"
    Then I should see "each" for "Unit Of Quantity" select
    And I should not see "set" for "Unit Of Quantity" select
    And I should not see "item" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    When I click "Add Kit Item"
    Then I should see "each" for "Kit Item 1 Product Unit" select
    And I should not see "set" for "Kit Item 1 Product Unit" select
    And I should not see "item" for "Kit Item 1 Product Unit" select
    And I should not see "hour" for "Kit Item 1 Product Unit" select
    When click "AddPrice"
    Then I should see "each" for "Price Unit" select
