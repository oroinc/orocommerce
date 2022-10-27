@fixture-OroSaleBundle:Quote.yml
@fixture-OroSaleBundle:LoadQuoteEmailTemplate.yml
@ticket-BB-19484
Feature: Create email campaign from marketing list based on Quote entity
  As an administrator
  I want to send emails to the customer users, who created Quotes in OroCommerce using the email campaign functionality

  Scenario: Setup Email Campaign by Marketing list based on Quote entity
    Given I login as administrator
    And I go to Marketing/Marketing Lists
    Then I click "Create Marketing List"
    And I fill form with:
      | Name   | Quote marketing list |
      | Entity | Quote                |
      | Type   | Dynamic              |
    And I add the following columns:
      | Contact Information |
      | Quote #             |
    And I save and close form
    Then I should see "Marketing List saved" flash message
    And I should see "AmandaRCole@example.org"
    Then I go to Marketing/Email Campaigns
    And I click "Create Email Campaign"
    Then I fill form with:
      | Name            | Quote Email campaign  |
      | Marketing List  | Quote marketing list  |
      | Schedule        | Manual                |
      | Transport       | Oro                   |
      | Template        | test_template         |
    And I save and close form
    Then I should see "Email Campaign saved" flash message
    And I should see "AmandaRCole@example.org"
    And I click "Send"
    Then Email should contains the following:
      | To      | AmandaRCole@example.org    |
      | Subject | Test Subject               |
