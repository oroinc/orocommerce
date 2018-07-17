@ticket-BB-14699
@fixture-OroRFPBundle:RFQ_product_unit_translation.yml
@regression
@skip

Feature: RFQ Product Unit Translation
  In order to ensure product units are transalatable
  As a Buyer
  I need to see translated product unit on Request a Quote page

  Scenario: Feature Background
    Given I enable the existing localizations
    And I login as administrator
    And I go to System/Localization/Translations
    And I click "Update Cache"
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Localization Switcher"
    And I select "Localization 1" localization

  Scenario: Create RFQ from shopping list
    Given I open page with shopping list "Shopping List 1"
    When I click "Request Quote"
    Then Request a Quote contains products
      | Product1 | 5 | item (lang1) |
