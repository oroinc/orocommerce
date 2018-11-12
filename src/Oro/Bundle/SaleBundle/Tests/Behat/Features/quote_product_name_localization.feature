@ticket-BB-14660
@fixture-OroSaleBundle:QuoteProductNameLocalization.yml

Feature: Quote Product Name Localization

  In order to ensure that product names in quotes are localizable on frontend
  As a Buyer
  I check product name on quote view page and quote choice page

  Scenario: Feature Background
    Given I enable the existing localizations
    And I signed in as AmandaRCole@example.org on the store frontend
    And I press "Localization Switcher"
    And I select "Localization 1" localization

  Scenario: Check product name is localized on quote view page and quote choice page
    Given I click "Quotes"
    When I click view Quote1 in grid
    Then I should see "Product1 (Localization 1)"
    When I click "Accept and Submit to Order"
    Then I should see "Product1 (Localization 1)"
