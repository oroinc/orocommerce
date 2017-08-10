@ticket-BB-11263
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroUserBundle:user.yml
Feature: Single Page Guest Checkout From Shopping List
  In order to complete the checkout process without going back and forth to various pages
  As a Guest customer
  I want to see all checkout information and be able to complete checkout on one page from "Shopping List" without having to register

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Enable guest shopping list setting
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Sales" on configuration sidebar
    And I click "Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    When I save form
    Then the "Enable guest shopping list" checkbox should be checked

  Scenario: Enable guest checkout setting
    Given I click "Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Change default guest checkout user owner
    Given uncheck "Use default" for "Default guest checkout owner" field
    And I fill form with:
      | Default guest checkout owner | Charlie Sheen |
    When I save form
    Then I should see "Charlie Sheen"

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I enable the existing warehouses
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Enable Single Page Checkout Workflow
    Given go to System/ Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Create Shopping List as unauthorized user from product view page
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping list"
    And I click "Shopping list"
    And I press "Create Order"
    And I fill "Billing Information Form" with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I fill "Shipping Information Form" with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check guest orders on backend
    Given I proceed as the Admin
    When I go to Sales/ Orders
    And I should see "Tester Testerson" in grid with following data:
      | Customer      | Tester Testerson |
      | Customer User | Tester Testerson |
      | Owner         | Charlie Sheen    |
