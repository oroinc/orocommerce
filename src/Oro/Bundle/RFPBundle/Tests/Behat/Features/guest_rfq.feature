@ticket-BB-10800
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Guest RFQ
  In order to collect potential sales from non-registered customers
  As a Sales rep
  I want that guest customers were able to submit RFQ for

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User | second_session |

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
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario:  Create RFQ from shopping list
    Given I proceed as the User
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List" for "PSKU1" product
    Then I should see "Product has been added to" flash message
    And I click "Shopping List"
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
    Given I proceed as the User
    And I am on the homepage
    When type "CONTROL1" in "search"
    And I click "Search Button"
    Then I should see "Control Product"
    And click "View Details"
    And click "LineItemDropdown"
    And click "Request a Quote"
    And I fill form with:
      | First Name             | Tester               |
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

  Scenario: Check that second RFQ assigned to the same customer
    Given I proceed as the Admin
    And go to Customers/ Customers
    And I should see "Tester Testerson" in grid with following data:
      | Group   | Non-Authenticated Visitors |
      | Account | Tester Testerson           |
    And I click view "Tester Testerson" in grid
    And I should see "PO Test 01"
    And I should see "PO Test 02"
