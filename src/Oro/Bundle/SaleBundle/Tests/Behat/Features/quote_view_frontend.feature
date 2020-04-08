@fixture-OroSaleBundle:QuoteViewFrontend.yml
@ticket-BB-16275
@regression

Feature: Quotes View Frontend

  In order to ensure frontend quote view page works correctly
  As a buyer
  I check product names are displayed properly on quote view page

  Scenario: Check quote view page
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I click "Quotes"
    And click view "Q123" in grid
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"
