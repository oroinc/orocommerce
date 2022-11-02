@ticket-BB-20526
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroShoppingListBundle:ShoppingListWithProductsFixture.yml

Feature: Guest shopping list in the list block
  In order to see products in shopping list information
  As a Guest
  I should be able to add and remove products to shopping list and see actual state of "In shopping list" block

  Scenario: Feature Background
    Given sessions active:
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Create shopping list on frontend
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see "Shopping list"
    And I should not see "In Shopping List"
    When type "PSKU1" in "search"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And I should see "In shopping list"
    When I am on homepage
    Then I should see "In Shopping List"

  Scenario: Create checkout and remove all products from shopping list
    When I open shopping list widget
    And I click "View List"
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
    And I click "Ship to This Address"
    And click "Continue"
    When I open shopping list widget
    And I click "View List"
    When I click "Delete" on row "PSKU1" in grid
    And I click "Yes, Delete"
    Then I should see "There are no shopping List line items"

  Scenario: Check shopping list items are correctly highlighted on homepage
    When I am on homepage
    Then I should not see "In Shopping List"
