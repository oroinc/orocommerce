@regression
@ticket-BB-15453
@fixture-OroUserBundle:manager.yml
@fixture-OroSaleBundle:QuoteProductFixture.yml
Feature: Backoffice Quote Forbids to Override a Product Price
  In order to check ACL "override quote prices"
  As an Administrator
  I want to check system behavior when the user tries to edit quote prices when it is forbidden

  Scenario: Create different window session
    Given sessions active:
      | Admin           | first_session  |
      | AdminEditQuotes | second_session |

  Scenario: Create a quote
    Given I proceed as the AdminEditQuotes
    And I login as administrator
    And go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | PO Number        | PO1   |
      | LineItemProduct  | PSKU1 |
    And I type "10" in "LineItemPrice"
    And I save form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message

  Scenario: Check that overriding product prices in quotes is forbidden
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/ User Management/ Roles
    And click edit "Administrator" in grid
    And I uncheck "Override quote prices"
    And I save form
    Then I should see "Role saved" flash message

    When I proceed as the AdminEditQuotes
    And I fill "Quote Form" with:
      | LineItemPrice | 1 |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Price overriding allowed by tier price only"

  Scenario: Change quote product prices when it allowed
    Given I proceed as the Admin
    And I check "Override quote prices"
    And I save form
    Then I should see "Role saved" flash message

    When I proceed as the AdminEditQuotes
    And I save and close form
    And agree that shipping cost may have changed
    Then I should not see alert
