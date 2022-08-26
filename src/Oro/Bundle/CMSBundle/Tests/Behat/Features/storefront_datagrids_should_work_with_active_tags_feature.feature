@regression
@ticket-BB-21620
@fixture-OroCMSBundle:CustomerUserFixture.yml
Feature: Storefront datagrids should work with active tags feature

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Activate tag feature for storefront related entities
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Entities / Entity Management

    And filter Name as is equal to "CustomerUser"
    And I click Edit CustomerUser in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message
    When I go to System / Entities / Entity Management

    And filter Name as is equal to "CustomerAddress"
    And I click Edit CustomerAddress in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message
    When I go to System / Entities / Entity Management

    And filter Name as is equal to "Order"
    And I click Edit Order in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message
    When I go to System / Entities / Entity Management

    And filter Name as is equal to "Quote"
    And I click Edit Quote in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

  Scenario: Sign in as customer user and check Address book, Order history and Quotes
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"

    When I click "Address Book"
    Then I should see "There are no company addresses"

    When I click "Order History"
    Then I should see "There are no open orders"

    When I click "Quotes"
    Then I should see "There are no quotes"
