@feature-BB-26056
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml

Feature: Quick Order Form RFQ Validation Not Displayed When RFQ Disabled
  Scenario: Create window sessions
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |

  Scenario: Enable quick order form for guest
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And I follow "Commerce/Sales/Quick Order" on configuration sidebar
    And fill "Quick Order Configuration Form" with:
      | Enable Guest Quick Order Form Default | false |
      | Enable Guest Quick Order Form         | true  |
    And click "Save settings"

  Scenario: Configure Out of Stock as Allowed Statuses for RFQ
    Given I proceed as the Admin
    And I login as administrator
    And go to System/Configuration
    When I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to RFQs" field
    And I fill form with:
      | Can Be Added to RFQs | [Out of Stock] |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Enable guest shopping list and RFQ
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And fill "Request For Quote Configuration Form" with:
      | Enable Guest RFQ Default | false |
      | Enable Guest RFQ         | true  |
      | Enable RFQ Default       | false |
      | Enable RFQ               | false |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Validate in-stock product on Quick Order Form as guest
    Given I proceed as the Guest
    And I am on the homepage
    And I should see text matching "Quick Order"
    And I click "Quick Order"
    And I should see "Add to Shopping List"
    And I should not see "Create Order"
    And I should not see "Get Quote"
    And I fill "Quick Order Form" with:
      | SKU1 | CC29 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 2 |
    And I should not see text matching "This product's inventory status does not allow adding it to an RFQ."
    When I click "Add to Shopping List"
    Then I should see '1 product was added (view shopping list)' flash message
