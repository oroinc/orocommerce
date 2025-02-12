@ticket-BB-24939
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Prevent duplicate shopping list when guest starts checkout multiple times

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Admin verifies shopping list count before guest checkout
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Shopping Lists
    Then records in grid should be 2

  Scenario: Guest creates a shopping list and starts checkout as a guest
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | Email           | Andy001@example.com |
      | First Name      | Andy                |
      | Last Name       | Derrick             |
      | Organization    | TestCompany         |
      | Street          | Fifth avenue        |
      | City            | Berlin              |
      | Country         | Germany             |
      | State           | Berlin              |
      | Zip/Postal Code | 10115               |
    And I click "Continue"

  Scenario: Admin verifies shopping list count after first guest checkout
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Shopping Lists
    Then records in grid should be 3

  Scenario: Guest updates shopping list and starts another checkout
    Given I proceed as the Guest
    And I am on the homepage
    When I open shopping list widget
    And I click "Open List"
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "10" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And click on "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | Email           | Andy002@example.com |
      | First Name      | Andy                |
      | Last Name       | Derrick             |
      | Organization    | TestCompany         |
      | Street          | Fifth avenue        |
      | City            | Berlin              |
      | Country         | Germany             |
      | State           | Berlin              |
      | Zip/Postal Code | 10115               |
    And I click "Continue"

  Scenario: Admin verifies shopping list count after second guest checkout
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Shopping Lists
    Then records in grid should be 3
