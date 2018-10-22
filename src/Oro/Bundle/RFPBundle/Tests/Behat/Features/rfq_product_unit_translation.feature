@ticket-BB-14699
@fixture-OroRFPBundle:RFQ_product_unit_translation.yml
@regression

Feature: RFQ Product Unit Translation
  In order to ensure product units are transalatable
  As a Buyer
  I need to see translated product unit on Request a Quote page

  Scenario: Feature Background
    Given I enable the existing localizations
    And I signed in as MarleneSBradley@example.com on the store frontend
    When I press "Localization Switcher"
    Then I select "Localization 1" localization

  Scenario: Create RFQ from shopping list
    Given I open page with shopping list "Shopping List 4"
    When I click "Request Quote"
    Then Request a Quote contains products
      | Product1 | 10 | item (lang1) |
