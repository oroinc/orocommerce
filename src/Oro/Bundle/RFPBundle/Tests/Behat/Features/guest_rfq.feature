@ticket-BB-10800
@ticket-BB-14758
@ticket-BB-21411
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

  Scenario: Enable guest RFQ functionality
    Given I proceed as the Admin
    And login as administrator
    And go to System/Configuration

    When I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And uncheck "Use default" for "Enable Guest RFQ" field
    And check "Enable Guest RFQ"
    And I save setting
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And check "Enable Guest Shopping List"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Update role permissions for guest users
    Given I go to Customers/Customer User Roles
    When I click edit "Non-Authenticated Visitors" in grid
    And select following permissions:
      | Request For Quote | View:User (Own) |
    And save and close form
    Then should see "Customer User Role has been saved" flash message

  Scenario: Submit RFQ from shopping list as Amanda
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    And hover on "Shopping List Widget"
    And click "View Details"
    And I click "More Actions"
    When I click "Request Quote"
    And click "Submit Request"
    Then I should see "Request has been saved" flash message
    And click "Sign Out"

  Scenario: Submit RFQ from shopping list as guest
    Given I am on the homepage
    And I should see "No Shopping Lists"
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List" for "PSKU1" product
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I hover on "Shopping List Widget"
    And I should see "1 Item | $0.00" in the "Shopping List Widget" element
    And I click on empty space
    And click "Request Quote"
    And I fill form with:
      | First Name    | Tester                |
      | Last Name     | Testerson             |
      | Email Address | testerson@example.com |
      | Phone Number  | 72 669 62 82          |
      | Company       | Red Fox Tavern        |
      | Role          | CEO                   |
      | Notes         | Test note for quote.  |
      | PO Number     | PO Test 01            |
    And click "Edit"
    And fill in "TargetPriceField" with "10.99"
    And click "Update"
    And click "Submit Request"
    Then should see "Request has been saved" flash message
    When I am on "/customer/rfp"
    Then should see "Tester Testerson"
    And should not see "Amanda Cole"

  Scenario: Check rfq in management console
    Given I proceed as the Admin
    When I go to Sales/ Requests For Quote
    Then I should see "Tester Testerson" in grid with following data:
      | SUBMITTED BY    | Tester Testerson |
      | CUSTOMER        | Tester Testerson |
      | INTERNAL STATUS | Open             |
      | PO NUMBER       | PO Test 01       |
    And click view "PO Test 01" in grid
    And should see " First Name Tester "
    And should see " Last Name Testerson "
    And should see " Company Red Fox Tavern "
    And should see " Customer Tester Testerson "
    And should see "Product1"
    When I click "Edit"
    And I click "Assigned To Tooltip Icon"
    Then I should see "The ID of the user who acts as an order fulfillment officer."
    When I click "Assigned Customer Users Tooltip Icon"
    Then I should see "The IDs of the customer users that will receive the order delivery."

  Scenario: Create second RFQ without adding to shopping list
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "PSKU1" in "search"
    And click "Search Button"
    Then I should see "Product1"
    When I click "View Details"
    And click "LineItemDropdown"
    And click "Request a Quote"
    And fill form with:
      | First Name    | Tester2               |
      | Last Name     | Testerson             |
      | Email Address | testerson@example.com |
      | Phone Number  | 72 669 62 82          |
      | Company       | Red Fox Tavern        |
      | Role          | CEO                   |
      | Notes         | Test note for quote.  |
      | PO Number     | PO Test 02            |
    And click on "Edit Request Product Line Item"
    And click on "Add Another Line"
    And type "20" in "Line Item Quantity"
    And click "Update"
    And click "Submit Request"
    Then I should see "Request has been saved" flash message
    And email with Subject "Your RFQ has been received." containing the following was sent:
      | Body | 1 item |
    And email with Subject "Your RFQ has been received." containing the following was sent:
      | Body | 20 item |

  Scenario: Check that second RFQ assigned to the different customer
    Given I proceed as the Admin
    And go to Customers/ Customers
    And should see "Tester Testerson" in grid with following data:
      | Group   | Non-Authenticated Visitors |
      | Account | Tester Testerson           |
    When I click view "Tester Testerson" in grid
    Then I should see "PO Test 01"
    And should not see "PO Test 02"

  Scenario: Create RFQ with another localization and check product unit in email
    Given I proceed as the Buyer
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "Localization 1" localization
    And I open shopping list widget
    And I click "View List"
    And click "Request Quote"
    And fill form with:
      | First Name    | Tester                |
      | Last Name     | Testerson             |
      | Email Address | testerson@example.com |
      | Company       | Red Fox Tavern        |
    And click "Submit Request"
    Then email with Subject "Your RFQ has been received." containing the following was sent:
      | Body | 1 item (lang1) |
