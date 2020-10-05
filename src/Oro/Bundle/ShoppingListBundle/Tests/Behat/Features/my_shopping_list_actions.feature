@regression
@ticket-BB-19469
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List Actions
  In order to manage shopping lists on front store
  As a Buyer
  I need to be able to manage shopping list using actions on shopping list view page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I set configuration property "oro_shopping_list.my_shopping_lists_page_enabled" to "1"

  Scenario: Check index page
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    When I click "My Shopping Lists"
    Then Page title equals to "My Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,818.00 | 32    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Duplicate Action
    Given I click View "Shopping List 3" in grid
    And I click "Shopping List Actions"
    When I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message
    When I open shopping list widget
    Then I should see "Shopping List 3 (Copied" in the "Shopping List Widget" element
    And I reload the page

  Scenario: Rename Action
    Given I click "Shopping List Actions"
    When I click "Rename"
    And I fill "Shopping List Rename Action Form" with:
      | Label | Shopping List 4 |
    And I click "Shopping List Action Submit"
    Then I should see "Shopping list has been successfully renamed" flash message
    When I open shopping list widget
    Then I should see "Shopping List 4" on shopping list widget
    And I reload the page

  Scenario: Set Default Action
    Given I click "Shopping List Actions"
    Then I should see "Set as Default"
    When I click "Set as Default"
    And I click "Yes, set as default"
    Then I should see "Shopping list has been successfully set as default" flash message

  Scenario: Delete Action
    Given I click "Shopping List Actions"
    When I click "Delete"
    And I click "Yes, delete"
    Then Page title equals to "My Shopping Lists - My Account"
    When I open shopping list widget
    Then I should not see "Shopping List 4" on shopping list widget
    And I reload the page

  Scenario: Re-assign Action
    Given I click View "Shopping List 3" in grid
    And I click "Shopping List Actions"
    When I click "Reassign"
    And I filter First Name as is equal to "Nancy" in "Shopping List Action Reassign Grid"
    And I click "Shopping List Action Reassign Radio"
    And I click "Shopping List Action Submit"
    Then I should see "Nancy Sallee"
    When I click "Nancy Sallee"
    Then Page title equals to "Nancy Sallee - Users - My Account"

  Scenario: Check shopping list view page without actions
    Given I follow "Account"
    And click "Users"
    And click "Roles"
    And click edit "Administrator" in grid
    And click "Shopping"
    When select following permissions:
      | Shopping List | Edit:None |
      | Shopping List | Assign:None |
      | Shopping List | Duplicate:None |
      | Shopping List | Delete:None |
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message
    When click "Sign Out"
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    And I click "My Shopping Lists"
    And I click View "Shopping List 3" in grid
    Then I should not see a "Shopping List Actions" element
