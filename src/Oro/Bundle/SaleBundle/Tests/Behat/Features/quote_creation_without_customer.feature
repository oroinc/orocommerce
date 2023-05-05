@regression
@ticket-BB-21196
@fixture-OroProductBundle:product_with_price.yml

Feature: Quote creation without customer
  In order to create a quote
  As an Administrator
  I want to have ability to to fill only shipping address and product sections

  Scenario: Create quote
    Given I login as administrator
    And I go to Sales / Quotes
    When I click "Create Quote"
    And I fill "Quote Form" with:
      | PO Number       | PO1_edit        |
      | LineItemProduct | PSKU1           |
    And I fill form with:
      | Organization    | Test Inc.       |
      | First Name      | Charlie         |
      | Last Name       | Sheen           |
      | Street          | 800 Scenic Hwy  |
      | City            | Haines City     |
      | Country         | United States   |
      | State           | Florida         |
      | Zip/Postal Code | 33844           |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message

  Scenario: Send quote to a customer
    When I click "Send to Customer"
    And I fill form with:
      | To | AmandaRCole@example.org |
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message
