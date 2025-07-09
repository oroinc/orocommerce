@ticket-BB-13658
@ticket-BB-16335
@ticket-BB-22546
@fixture-OroProductBundle:product_frontend_single_unit_mode.yml
@pricing-storage-combined
Feature: Product frontend single unit mode
  In order to successfully use single unit mode in the system
  As a Buyer
  I want to see correct prices for related products on product view page when single unit mode is enabled

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable Single Unit mode
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And uncheck "Use default" for "Default Primary Unit" field
    And I select "item" from "Default Primary Unit"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Add related products
    Given I go to Products/ Products
    When I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |
    And I click "Select products"
    And I click "Save and Close"
    Then I should see "Product has been saved" flash message

  Scenario: Check that prices are correctly displayed
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    Then I should see "$20.00" for "PSKU2" product
    And I should see "$30.00" for "PSKU3" product
    And I should see "$40.00" for "PSKU4" product

  Scenario: Check if price is displayed correctly on product view page if product unit is not as default
    Given I type "PSKU2" in "search"
    When I click "Search Button"
    And I click "View Details" for "PSKU2" product
    Then I should see an "Default Page Prices" element
    And I should see "Each 1+ $20.00" in the "Default Page Prices" element

  Scenario: Enable "Wide Template" view mode for product units
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Page Template | wide |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Verify that prices in "Related Products Block" are correctly displayed in "Wide Template" layout view
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU1" product
    Then I should see "$20.00" for "PSKU2" product
    And I should see "$30.00" for "PSKU3" product
    And I should see "$40.00" for "PSKU4" product

  Scenario: Enable "List page" view mode for product units
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Page Template | tabs |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Verify that prices in "Related Products Block" are correctly displayed in "Tabs Template" layout view
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "$20.00" for "PSKU2" product
    And I should see "$30.00" for "PSKU3" product
    And I should see "$40.00" for "PSKU4" product

  Scenario: Check if price is displayed correctly for existing product kit on view page
    Given I type "PSKU_KIT1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU_KIT1" product
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $30.00 |
    And I should see that the "Product Kit Line Item Totals Form Unit" element has a selected unit "set"
    And I should see that the "Product Kit Line Item Totals Form Unit" element has available units:
      | set  |
    And I should see that the "Product Kit Line Item Totals Form Unit" element has a product unit selector of type "single"

  Scenario: Create new product kit with Single Unit mode
    Given I proceed as the Admin
    When I go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Kit |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | PSKU_KIT2      |
      | Name   | PSKU_KIT2 Name |
      | Status | Enable         |
    And I fill "ProductKitForm" with:
      | Kit Item 1 Label            | Kit Item 1 |
      | Kit Item 1 Sort Order       | 1          |
      | Kit Item 1 Minimum Quantity | 1          |
      | Kit Item 1 Maximum Quantity | 2          |
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 50                 |
      | Currency   | $                  |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check if price is displayed correctly for new product kit on view page
    Given I proceed as the Buyer
    And I reload the page
    And I type "PSKU_KIT2" in "search"
    When I click "Search Button"
    And I click "View Details" for "PSKU_KIT2" product
    And I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $90.00 |
    And I should see that the "Product Kit Line Item Totals Form Unit" element has a selected unit "item"
    And I should see that the "Product Kit Line Item Totals Form Unit" element has available units:
      | item |
    And I should see that the "Product Kit Line Item Totals Form Unit" element has a product unit selector of type "single"
    And I close ui dialog

  Scenario: As guest user verify that prices are correctly displayed in "Tabs Template" layout view
    When I click "Account Dropdown"
    And I click "Sign Out"
    And I type "PSKU2" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU2" product
    And I should not see "Price unavailable for this quantity"
