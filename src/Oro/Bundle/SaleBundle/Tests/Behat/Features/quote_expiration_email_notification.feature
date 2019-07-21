@regression
@ticket-BB-16150
@ticket-BB-16831
@automatically-ticket-tagged
@fixture-OroSaleBundle:QuoteBackofficeApprovalsFixture.yml
Feature: Quote expiration email notification
  In order to be aware of expired quotes
  As an Administrator
  I want to receive email notification when Quote's marked expired

  Scenario: Set timezone for the application
    Given I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    And I fill form with:
      | Timezone | America/New York |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create Quote and send it to customer
    When I go to Sales/ Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer User   | Amanda Cole |
      | LineItemProduct | AA1         |
      | LineItemPrice   | 1           |
    When I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #31 successfully sent to customer" flash message

  Scenario: Notification is sent when Quote's marked as expired
    When I click "Expire"
    And click "Mark as Expired"
    Then I should see "Quote #31 was successfully marked as expired" flash message
    And email with Subject "Quote #31 has expired" was not sent
    And email with Subject "Quote #31 has marked as expired" containing the following was sent:
      | To   | admin@example.com                           |
      | Body | Quote #31 has marked as expired by John Doe |
    And email date less than "-2 hours"
    And email date greater than "-6 hours"

  Scenario: Create Expired Quote and send it to customer
    When I go to Sales/ Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer User   | Amanda Cole                      |
      | LineItemProduct | AA1                              |
      | LineItemPrice   | 1                                |
      | Valid Until     | <DateTime:Jul 1, 2017, 12:00 AM> |
    When I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #32 successfully sent to customer" flash message

  Scenario: Notification is sent after automatic quotes expiration is performed
    When automatic expiration of old quotes has been performed
    Then email with Subject "Quote #32 has expired" containing the following was sent:
      | To   | admin@example.com        |
      | Body | Quote #32 has expired on |
    And email with Subject "Quote #32 has marked as expired" was not sent

  Scenario: Create quote which expires original quote upon acceptance
    When I go to Sales/ Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer User   | Amanda Cole                      |
      | LineItemProduct | AA1                              |
      | LineItemPrice   | 1                                |
    When I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #33 successfully sent to customer" flash message

    When I click "Create new Quote"
    And I fill form with:
      | Expire Existing Quote | Upon Acceptance |
    And I click "Submit"
    Then I should see "Quote #34 successfully created" flash message

    When I click "Send to Customer"
    And I click "Send"
    Then I should see "Quote #34 successfully sent to customer" flash message
    When Quote "34" is marked as accepted by customer
    And I reload the page
    Then should see Quote with:
      | Quote #         | 34       |
      | Customer Status | Accepted |
    And email with Subject "Quote #33 has expired" containing the following was sent:
      | To   | admin@example.com        |
      | Body | Quote #33 has expired on |

    When I open Quote with qid 33
    Then should see Quote with:
      | Quote #         | 33      |
      | Internal Status | Expired |
