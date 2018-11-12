@ticket-BB-12801
@fixture-OroProductBundle:ProductsExportFixture.yml

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
    And I select "item" from "Default Primary Unit"
    And I save setting

  Scenario: Create price with Single Unit mode
    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type |Simple  |
    And click "Continue"
    Then I should see "item" for "Unit Of Quantity" select
    And I should not see "each" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    And click "AddPrice"
    Then I should see "item" for "Price Unit" select
    And I click "Cancel"

  Scenario: Edit product with Single Unit mode
    When go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should see "item" for "Unit Of Quantity" select
    And I should not see "each" for "Unit Of Quantity" select
    And I should not see "hour" for "Unit Of Quantity" select
    And click "AddPrice"
    Then I should see "item" for "Price Unit" select
