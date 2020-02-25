@feature-BB-12800
@fixture-OroOrderBundle:order.yml
@fixture-OroOrderBundle:OrderWithConfigurableProduct.yml

Feature: Removed products in order history
  In order to store products information in order
  As a Buyer
  I want to see products information in order even if products already removed

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | Buyer  |second_session|

  Scenario: Administrator delete product
    Given I operate as the Admin
    And I login as administrator
    When I go to Products/Products
    And I click delete "Product1" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message
    When I click delete "Slip-On Clog" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message
    When I click delete "White Slip-On Clog M" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message
    When I click delete "Black Slip-On Clog L" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message

  Scenario: Administrator must see deleted products in order history
    Given I go to Sales/Orders
    And I click view "SimpleOrder" in grid
    Then I should see AA1 in grid with following data:
      | Product   | Product1 |
      | Quantity  | 10       |
      | Price     | $5.00    |
    When I go to Sales/Orders
    And I click view "configurableOrder" in grid
    Then I should see 1GB81 in grid with following data:
      | Product   | Slip-On Clog |
      | Quantity  | 10           |
      | Price     | $5.00        |
    And I should see 1GB82 in grid with following data:
      | Product   | Slip-On Clog |
      | Quantity  | 10           |
      | Price     | $5.00        |

  Scenario: Customer User must see deleted products in order history
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I follow "Account"
    And I click "Order History"
    And I click view "SimpleOrder" in grid
    Then I should see AA1 in grid with following data:
      | Product   | Product1 Item #: AA1 |
      | Quantity  | 10 items             |
      | Price     | $5.00                |
    When I click "Order History"
    And I click view "configurableOrder" in grid
    Then I should see 1GB81 in grid with following data:
      | Product   | Slip-On Clog Item #: 1GB81 |
      | Quantity  | 10 items                   |
      | Price     | $5.00                      |
    And I should see 1GB82 in grid with following data:
      | Product   | Slip-On Clog Item #: 1GB82 |
      | Quantity  | 10 items                   |
      | Price     | $5.00                      |
