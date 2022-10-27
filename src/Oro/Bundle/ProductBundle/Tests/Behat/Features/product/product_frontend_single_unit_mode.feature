@ticket-BB-13658
@ticket-BB-16335
@fixture-OroProductBundle:product_frontend_single_unit_mode.yml

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
    Then I should see "Your Price: $20.00 / each" for "PSKU2" product
    And I should see "Your Price: $30.00 / set" for "PSKU3" product
    And I should see "Your Price: $40.00 / item" for "PSKU4" product

  Scenario: Check if price is displayed correctly on product view page if product unit is not as default
    Given I type "PSKU2" in "search"
    When I click "Search Button"
    And I click "View Details" for "PSKU2" product
    Then I should see an "Default Page Prices" element
    And I should see "Each 1 $20.00" in the "Default Page Prices" element

  Scenario: Enable "Short page" view mode for product units
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    When I fill "Page Templates Form" with:
      | Use Default  | false      |
      | Product Page | Short page |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify that prices in "Related Products Block" are correctly displayed in "Short page" layout view
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU1" product
    Then I should see "Your Price: $20.00 / each" for "PSKU2" product
    And I should see "Your Price: $30.00 / set" for "PSKU3" product
    And I should see "Your Price: $40.00 / item" for "PSKU4" product

  Scenario: Enable "Two columns page" view mode for product units
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    When I fill "Page Templates Form" with:
      | Use Default  | false            |
      | Product Page | Two columns page |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify that prices in "Related Products Block" are correctly displayed in "Two columns page" layout view
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Your Price: $20.00 / each" for "PSKU2" product
    And I should see "Your Price: $30.00 / set" for "PSKU3" product
    And I should see "Your Price: $40.00 / item" for "PSKU4" product

  Scenario: Enable "List page" view mode for product units
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    When I fill "Page Templates Form" with:
      | Use Default  | false     |
      | Product Page | List page |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify that prices in "Related Products Block" are correctly displayed in "List page" layout view
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Your Price: $20.00 / each" for "PSKU2" product
    And I should see "Your Price: $30.00 / set" for "PSKU3" product
    And I should see "Your Price: $40.00 / item" for "PSKU4" product

  Scenario: As guest user verify that prices are correctly displayed in "List page" layout view
    When I click "Sign Out"
    When I type "PSKU2" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU2" product
    Then I should see "Listed Price: $20.00 / each"
    And I should not see "Price for requested quantity is not available"
