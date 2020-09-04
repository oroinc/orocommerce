@fixture-OroOrderBundle:order.yml
@fixture-OroOrderBundle:LoadOrderEmailTemplate.yml
@ticket-BB-19484
Feature: Create email campaign from marketing list based on Order entity
  As an administrator
  I want to send emails to the customer users, who created Orders using the email campaign functionality

  Scenario: Setup Email Campaign by Marketing list based on Order entity
    Given I login as administrator
    And I go to Marketing/Marketing Lists
    Then I click "Create Marketing List"
    And I fill form with:
      | Name   | Order marketing list |
      | Entity | Order                |
      | Type   | Dynamic                      |
    And I add the following columns:
      | Contact Information |
      | Order Number        |
    And I save and close form
    Then I should see "Marketing List saved" flash message
    And I should see "AmandaRCole@example.org"
    Then I go to Marketing/Email Campaigns
    And I click "Create Email Campaign"
    Then I fill form with:
      | Name            | Order Email campaign  |
      | Marketing List  | Order marketing list  |
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
