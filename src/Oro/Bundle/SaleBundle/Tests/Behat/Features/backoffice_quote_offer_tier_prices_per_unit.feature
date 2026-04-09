@ticket-BB-25250
@regression
@fixture-OroSaleBundle:QuoteOfferTierPricesPerUnit.yml

Feature: Backoffice quote offer tier prices per unit
  In order to select correct price for a specific unit of measure
  As an Administrator
  I should see tier prices matching the selected unit when adding a quote product offer

  Scenario: Create a quote and select price from tier prices popup for set unit
    Given I login as administrator
    When I go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | AA1 |
    And I click "Add Offer"
    And I fill "Quote Form" with:
      | Unit of Line Item Secondary Offer | set |
    And I click "Tier prices button of Secondary Offer"
    Then I should see "Click to select price per unit"
    When I click "$10.00"
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And I should see "AA1 Product1 1 item or more $2.00"
    And I should see "1 set or more $10.00"
