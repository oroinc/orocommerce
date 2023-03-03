@regression
@ticket-BB-22262
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml

Feature: Backoffice quote more then one offer for one product
  In order to have more than one offer with different unit for one product
  As an Administrator
  I should see all units under different offers in one line item are not been unset when clicking add offer button

  Scenario: Create a quote
    Given I login as administrator
    When I go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | AA1 |
    And I click "Add Offer"
    And I fill "Quote Form" with:
      | Price of Line Item Secondary Offer    | 12  |
      | Quantity of Line Item Secondary Offer | 1   |
      | Unit of Line Item Secondary Offer     | set |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And I should see "AA1 Product1 1 item or more $2.00"
    And I should see "1 set or more $12.00"

  Scenario: Adding new offer should not reset units of all others.
    When I click "Edit"
    And I click "Add Offer"
    Then "Quote Form" must contains values:
      | Unit of Line Item Secondary Offer | set |
    When I click "Cancel"
    Then I should see "AA1 Product1 1 item or more $2.00"
    And I should see "1 set or more $12.00"
