@regression
@ticket-BB-20491
@fixture-OroOrderBundle:order_product_with_different_localization.yml

Feature: Order History in RTL
  In order to be able to browse past orders when in RTL locale
  As a Buyer
  I want see that Past Orders datagrid works correctly

  Scenario: Feature Background
    Given I enable the existing localizations
    And sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Localizations
    And I click Edit English in grid
    And I fill form with:
      | Formatting      | Arabic |
      | Enable RTL Mode | true   |
    And I save and close form
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Checks that Created At filter works correctly in Past Orders datagrid
    Given I follow "Account"
    And I click "Order History"
    And I should see following records in "PastOrdersGrid":
      | ORD#1 |
    When I filter Created At as between "today-2" and "today-1" in "PastOrdersGrid"
    Then I should not see "ORD#1"
    When I filter Created At as between "today-1" and "today+1" in "PastOrdersGrid"
    Then I should see following records in "PastOrdersGrid":
      | ORD#1 |
