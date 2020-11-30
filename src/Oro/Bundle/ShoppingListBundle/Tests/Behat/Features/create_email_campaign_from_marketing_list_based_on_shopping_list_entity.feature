@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroShoppingListBundle:LoadShoppingListEmailTemplate.yml
@ticket-BB-19484
Feature: Create email campaign from marketing list based on Shopping List entity
  As an administrator
  I want to send emails to the customer users,
  who created Shopping Lists in OroCommerce using the email campaign functionality

  Scenario: Setup Email Campaign by Marketing list based on Shopping List
    Given I login as administrator
    And I go to Marketing/Marketing Lists
    Then I click "Create Marketing List"
    And I fill form with:
      | Name   | Shopping List marketing list |
      | Entity | Shopping List                |
      | Type   | Dynamic                      |
    And I add the following columns:
      | Contact Information |
      | Owner               |
    And I save and close form
    Then I should see "Marketing List saved" flash message
    And I should see "AmandaRCole@example.org"
    And I should see "MarleneSBradley@example.com"
    Then I go to Marketing/Email Campaigns
    And I click "Create Email Campaign"
    Then I fill form with:
      | Name            | Shopping List Email campaign  |
      | Marketing List  | Shopping List marketing list  |
      | Schedule        | Manual                        |
      | Transport       | Oro                           |
      | Template        | test_template                 |
    And I save and close form
    Then I should see "Email Campaign saved" flash message
    And I should see "AmandaRCole@example.org"
    And I should see "MarleneSBradley@example.com"
    And I click "Send"
    Then Email should contains the following:
      | To      | AmandaRCole@example.org    |
      | Subject | Test Subject               |
    Then Email should contains the following:
      | To      | MarleneSBradley@example.com |
      | Subject | Test Subject                |
