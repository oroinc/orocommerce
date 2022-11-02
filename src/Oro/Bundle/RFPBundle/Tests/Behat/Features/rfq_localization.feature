@ticket-BB-14699
@ticket-BB-16275
@fixture-OroRFPBundle:RFQ_localization.yml
@regression

Feature: RFQ Localization
  In order to ensure product units are transalatable
  As a Buyer
  I need to see translated product unit on Request a Quote page
  I need to see that product name is displayed properly on Request a Quote page

  Scenario: Feature Background
    Given I enable the existing localizations
    And I signed in as MarleneSBradley@example.com on the store frontend
    When I press "Localization Switcher"
    Then I select "Localization 1" localization

  Scenario: Create RFQ from shopping list
    Given I open page with shopping list "Shopping List 4"
    When I click "More Actions"
    When I click "Request Quote"
    Then Request a Quote contains products
      | Product1`"'&йёщ®&reg;> | 10 | item (lang1) |

  Scenario: Create RFQ and ensure product name is displayed properly
    When I click "Submit Request"
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

