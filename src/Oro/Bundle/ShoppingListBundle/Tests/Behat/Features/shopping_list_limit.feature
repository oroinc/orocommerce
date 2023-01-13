@ticket-BB-10050-limit
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Shopping list limit
  As Administrator I have a possibility to restrict limit of shopping lists for customer in configuration

  Scenario: Unlimited shopping list default configuration check
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Use default" checkbox should be checked

  Scenario: Unlimited shopping list on frontend
    Given I operate as the Buyer
    And type "Prod1" in "search"
    And click "Search Button"
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shopping List"
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message and I close it
    And I should see "In shopping list"
    And I should see "1 Shopping List"
    When I open shopping list widget
    And I click "Create New List"
    And I click "Create"
    Then I should see "2 Shopping Lists"

  Scenario: Remove one shopping list
    Given I open shopping list widget
    When I click "View Details"
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete" in modal window
    Then I should see "Shopping List deleted" flash message
    And I should see "1 Shopping List"

  Scenario: Set limit to One shopping list in configuration
    Given I operate as the Admin
    And I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check limit is applied on frontend
    Given I operate as the Buyer
    When I reload the page
    Then I should not see "1 Shopping List"
    And I should see "Shopping List"
    And I open shopping list widget
    And I should not see "Create New List"

  Scenario: New Site creation
    Given I operate as the Admin
    When go to System/ Websites
    And click "Create Website"
    And fill form with:
      |Name                           |NewSite                   |
      |Guest Role                     |Non-Authenticated Visitors|
      |Default Self-Registration Role |Buyer                     |
    And save and close form
    Then should see "Website has been saved" flash message
    When go to System/ Websites
    And click "Set default" on row "NewSite" in grid
    And click "Configuration" on row "Default" in grid
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing General form" with fictional website
    And submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check new site is not affected by limit
    Given I operate as the Buyer
    And reload the page
    And I open shopping list widget
    Then I should see "Create New List"
    When click "Create New List"
    And type "New Front Shopping List" in "Shopping List Name"
    And click "Create"
    Then should see "New Front Shopping List"
    And I open shopping list widget
    Then should see "New Front Shopping List"

