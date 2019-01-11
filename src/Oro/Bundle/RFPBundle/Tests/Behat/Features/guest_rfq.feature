@ticket-BB-10800
@ticket-BB-14758
@fixture-OroRFPBundle:GuestRFQ.yml
Feature: Guest RFQ
  In order to collect potential sales from non-registered customers
  As a Sales rep
  I want to be able to check RFQs submitted by guest customers
  As a Guest Customer
  I want to be able to submit RFQs from shopping list page or by using product line item dropdown
  I want to see localized product units in RFQ confirmation email

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations

  Scenario: Enable guest RFQ
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And uncheck "Use default" for "Enable Guest RFQ" field
    And I check "Enable Guest RFQ"
    And I save setting
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    When I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Create RFQ from shopping list
    Given I proceed as the Buyer
    And I am on the homepage
    And I should see "No Shopping Lists"
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List" for "PSKU1" product
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I hover on "Shopping List Widget"
    And I should see "1 Item | $0.00" in the "Shopping List Widget" element
    And click "Request Quote"
    And I fill form with:
      | First Name             | Tester               |
      | Last Name              | Testerson            |
      | Email Address          | testerson@example.com|
      | Phone Number           | 72 669 62 82         |
      | Company                | Red Fox Tavern       |
      | Role                   | CEO                  |
      | Notes                  | Test note for quote. |
      | PO Number              | PO Test 01           |
    And click "Edit"
    And I fill in "TargetPriceField" with "10.99"
    And click "Update"
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And I should see "Thank You For Your Request!"

  Scenario: Check rfq in management console
    Given I proceed as the Admin
    When I go to Sales/ Requests For Quote
    Then I should see "Tester Testerson" in grid with following data:
      | SUBMITTED BY    | Tester Testerson     |
      | CUSTOMER        | Tester Testerson     |
      | INTERNAL STATUS | Open                 |
      | PO NUMBER       | PO Test 01           |
    And I click view "PO Test 01" in grid
    And I should see " First Name Tester "
    And I should see " Last Name Testerson "
    And I should see " Company Red Fox Tavern "
    And I should see " Customer Tester Testerson "
    And I should see "Product1"

  Scenario: Create second RFQ without adding to shopping list
    Given I proceed as the Buyer
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    And click "View Details"
    And click "LineItemDropdown"
    And click "Request a Quote"
    And I fill form with:
      | First Name             | Tester2              |
      | Last Name              | Testerson            |
      | Email Address          | testerson@example.com|
      | Phone Number           | 72 669 62 82         |
      | Company                | Red Fox Tavern       |
      | Role                   | CEO                  |
      | Notes                  | Test note for quote. |
      | PO Number              | PO Test 02           |
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And I should see "Thank You For Your Request!"

  Scenario: Check that second RFQ assigned to the different customer
    Given I proceed as the Admin
    And go to Customers/ Customers
    And I should see "Tester Testerson" in grid with following data:
      | Group   | Non-Authenticated Visitors |
      | Account | Tester Testerson           |
    When I click view "Tester Testerson" in grid
    Then I should see "PO Test 01"
    And I should not see "PO Test 02"

  Scenario: Create RFQ with another localization and check product unit in email
    Given I proceed as the Buyer
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "Localization 1" localization
    And I open shopping list widget
    And I click "View Details"
    And click "Request Quote"
    And I fill form with:
      | First Name             | Tester               |
      | Last Name              | Testerson            |
      | Email Address          | testerson@example.com|
      | Company                | Red Fox Tavern       |
    When I click "Submit Request"
    Then email with Subject "Your RFQ has been received." containing the following was sent:
      | Body    | 1 item (lang1) |
