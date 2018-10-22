@ticket-BAP-17399
@fixture-OroApplicationProBundle:Products_quick_order_form.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Checkout start transition permissions
  In order to manage transition permissions
  As a store administrator
  I want to be able to choose transition permissions from list

  Scenario: Feature Background
    Given sessions active:
      | Admin    |first_session |
      | User     |second_session|

  Scenario: Checking that stat transition permissions are available for edit
    Given I proceed as the Admin
    And I login as administrator
    And go to Customers/Customer User Roles
    And I click Edit Buyer in grid
    When I expand "Checkout" permissions in "Workflows" section
    And I click Perform Transition permissions for "Start Checkout From Quick Order Form" transition
    Then I should see next items in permissions dropdown:
      | None |
      | Full |

  Scenario: Select "None" permission for stat transition
    Given I choose "None" in permissions dropdown
    And save form
    And I proceed as the User
    And I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    Then I should not see following buttons:
      |Create Order|

  Scenario: Select "Full" permission for stat transition
    Given I proceed as the Admin
    When I expand "Checkout" permissions in "Workflows" section
    And I click Perform Transition permissions for "Start Checkout From Quick Order Form" transition
    And choose "Full" in permissions dropdown
    And save form
    And I proceed as the User
    And reload the page
    Then I should see "Create Order" button enabled
