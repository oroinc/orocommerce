@regression
@ticket-BB-15633
@fixture-OroOrderBundle:order_product_with_different_localization.yml
Feature: Localized product names in Product History
  In order to manage products on the customer order page
  As customer user
  I want see translated product names after localization switch and product remove

  Scenario: Feature Background
    Given I enable the existing localizations
    And sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    And I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open Order History page on the store frontend
    And I click "View" on row "ORD#1" in grid "PastOrdersGrid"

  Scenario: Check localized product names
    Given I should be on Order Frontend View page
    And I should see following "OrderLineItemsGrid" grid:
      | Product              |
      | Product1 Item #: AA1 |
      | Product2 Item #: AA2 |
    When I click "Localization Switcher"
    And I select "Zulu" localization
    Then I should see following "OrderLineItemsGrid" grid:
      | Product                   |
      | Product1 Zulu Item #: AA1 |
      | Product2 Zulu Item #: AA2 |

  Scenario: Remove product
    Given I proceed as the Admin
    And I click delete Product2 in grid
    And click "Yes, Delete"

  Scenario: Check product names in Order which contains removed product
    Given I proceed as the User
    When I reload the page
    Then I should see following "OrderLineItemsGrid" grid:
      | Product                   |
      | Product1 Zulu Item #: AA1 |
      | Product2 Item #: AA2      |
