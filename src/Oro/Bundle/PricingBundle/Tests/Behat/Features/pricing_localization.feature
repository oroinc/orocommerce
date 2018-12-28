@ticket-BB-13720
@fixture-OroPricingBundle:PricingLocalization.yml
@regression
Feature: Pricing Localization
  In order to have properly localized product pricing block
  As a User
  I need to be able to see localized product units for product in product catalog

  Scenario: Feature Background
    Given I enable the existing localizations

  Scenario: Check translation of product unit in product catalog
    Given I am on the homepage
    And I click "Localization Switcher"
    And I select "Localization 1" localization
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Listed Price: US$6.00 / item (lang1)" for "PSKU1" product
    And I should see "Your Price: US$6.00 / item (lang1)" for "PSKU1" product

  Scenario: Check translation of product unit in product catalog in mobile view
    Given I set window size to 320x640
    Then I should see "Listed Price: US$6.00 / item (short, lang1)" for "PSKU1" product
    And I should see "Your Price: US$6.00 / item (short, lang1)" for "PSKU1" product
