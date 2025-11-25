@regression
@fixture-OroShoppingListBundle:GuestShoppingListsFixture.yml
@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Guest RFQ Button Not Visible For Invalid Items
  In order to ensure RFQ button visibility respects product inventory status and RFQ feature
  As a Guest user
  I need to verify that Request Quote button is not visible for invalid RFQ items
  and when RFQ feature is disabled

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Enable Guest Shopping List
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Configure Out of Stock as Allowed Statuses for RFQ
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to RFQs" field
    And I fill form with:
      | Can Be Added to RFQs | [Out of Stock] |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Disable Guest RFQ
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And uncheck "Use default" for "Enable Guest RFQ" field
    And I fill "Request For Quote Configuration Form" with:
      | Enable Guest RFQ Default | false |
      | Enable Guest RFQ         | false |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Add in-stock product to shopping list as guest and verify Request Quote button is not visible when Guest RFQ is disabled
    Given I proceed as the Guest
    And I am on homepage
    When I type "SKU003" in "search"
    And I click "Search Button"
    Then I should see "Product3"
    When I click "View Details" for "SKU003" product
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And click "Open List"
    Then I should see following grid:
      | SKU    | Product  | Qty Update All  | Price | Subtotal |
      | SKU003 | Product3 | 1 item ( each ) | $3.00 | $3.00    |
    And I should not see "Request Quote" button





