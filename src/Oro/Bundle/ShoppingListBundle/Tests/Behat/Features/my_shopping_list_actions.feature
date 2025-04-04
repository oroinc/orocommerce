@regression
@ticket-BB-19469
@ticket-BB-21750
@fix-BB-24936
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List Actions
  In order to manage shopping lists on front store
  As a Buyer
  I need to be able to manage shopping list using actions on shopping list view page

  Scenario: Check index page
    Given I login as AmandaRCole@example.org buyer
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    Then Page title equals to "Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,818.00 | 32    | Yes     |
      | Shopping List 1 | $1,581.00 | 3     | No      |
    And records in grid should be 2
    When I open shopping list widget
    Then I should see "Shopping List 1" on shopping list widget
    And I should see "Shopping List 3" on shopping list widget
    And I reload the page

  Scenario: Duplicate Action
    Given I click View "Shopping List 3" in grid
    When I click "Shopping List Actions"
    And I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    When I open shopping list widget
    Then I should see "Shopping List 3 (Copied" in the "Shopping List Widget" element
    And I reload the page

  Scenario: Rename Action
    Given I click "Shopping List Actions"
    When I click "Rename"
    And I fill "Shopping List Rename Action Form" with:
      | Label | Shopping List 4 |
    And I click "Shopping List Action Submit"
    Then I should see "Shopping list has been successfully renamed" flash message and I close it
    When I open shopping list widget
    Then I should see "Shopping List 4" on shopping list widget
    And I close shopping list widget
    And I should see "Checkout"
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I click "Account Dropdown"
    Then I click on "Shopping Lists"
    And I click View "Shopping List 4" in grid

  Scenario: Set Default Action
    Given I click "Shopping List Actions"
    Then I should see "Set as Default"
    When I click "Set as Default"
    And I click "Yes, set as default"
    Then I should see "Shopping list has been successfully set as default" flash message and I close it
    And reload the page
    And I wait for 3 seconds
    When I click "Shopping List Actions"
    Then I should see "Set As Default" button disabled
    When I open shopping list widget
    And I click on "Shopping List Widget Set Current Radio 2"
    And I click on empty space
    And I click on "Flash Message Close Button"
    And I click "Shopping List Actions"
    Then I should see "Set as Default"

  Scenario: Check Default Shopping List
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    Then I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 4 | $8,818.00 | 32    | No      |
      | Shopping List 3 | $8,818.00 | 32    | No      |
      | Shopping List 1 | $1,581.00 | 3     | Yes     |
    And records in grid should be 3

  Scenario: Delete Action
    When I click Edit "Shopping List 4" in grid
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    And click on "Flash Message Close Button"
    Then Page title equals to "Shopping Lists - My Account"
    When I open shopping list widget
    Then I should not see "Shopping List 4" on shopping list widget
    And I reload the page

  Scenario: Move line item to another shopping list
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And Page title equals to "Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,818.00 | 32    | Yes     |
      | Shopping List 1 | $1,581.00 | 3     | No      |
    And I click Edit "Shopping List 3" in grid
    And I sort grid by "SKU"
    When I click on "First Line Item Row Checkbox"
    And I click "Move to" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    And I click "Filter Toggle" in "UiDialog" element
    And I filter Name as is equal to "Shopping List 1" in "Shopping List Action Move Grid"
    And I click "Show (1)"
    And I click "Shopping List Action Move Radio"
    And I click "Shopping List Action Submit"
    Then I should see "One entity has been moved successfully" flash message and I close it
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And Page title equals to "Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,785.00 | 31    | Yes     |
      | Shopping List 1 | $1,614.00 | 4     | No      |

  Scenario: Delete line items from shopping list mass action
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And Page title equals to "Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,785.00 | 31    | Yes     |
      | Shopping List 1 | $1,614.00 | 4     | No      |
    And I filter Name as is equal to "Shopping List 1"
    And I click Edit "Shopping List 1" in grid
    When I check first 4 records in "Frontend Shopping List Edit Grid"
    And I click "Delete" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    And confirm deletion
    Then I should see "4 item(s) have been deleted successfully" flash message and I close it
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And Page title equals to "Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,785.00 | 31    | Yes     |
      | Shopping List 1 | $0.00     | 0     | No      |

  Scenario: Re-assign Action
    Given I click View "Shopping List 3" in grid
    When I click "Shopping List Actions"
    And I click "Reassign"
    And I filter First Name as is equal to "Nancy" in "Shopping List Action Reassign Grid"
    And I click "Show (1)"
    And I click "Shopping List Action Reassign Radio"
    And I click "Shopping List Action Submit"
    Then I should see "Nancy Sallee"
    When I click "Nancy Sallee"
    Then Page title equals to "Nancy Sallee - Users - My Account"

  Scenario: Check shopping list view page without actions
    And I click "Account Dropdown"
    And click "Roles"
    And click edit "Administrator" in grid
    And I should see 'Predefined roles cannot be edited directly. We copied all the original data so that you can save it as a new user role for your organization. All users will be moved from the original role to this new role after you click "Save".' flash message and I close it
    And click "Shopping"
    And select following permissions:
      | Shopping List | Edit:None           |
      | Shopping List | Assign:None         |
      | Shopping List | Duplicate:None      |
      | Shopping List | Delete:None         |
      | Shopping List | Rename:None         |
      | Shopping List | Set as Default:None |
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message and I close it
    And I click "Account Dropdown"
    When click "Sign Out"
    And I login as AmandaRCole@example.org buyer
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I click View "Shopping List 1" in grid
    Then I should not see a "Shopping List Actions" element

  Scenario: Filter by owner should be visible after clearing all filters
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And Page title equals to "Shopping Lists - My Account"
    When I click "Frontend Grid Action Filter Button"
    And I filter Owner as contains "Amanda"
    And I click "Clear All Filters"
    Then I should see "Owner" filter in frontend grid
