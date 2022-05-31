@regression
@ticket-BB-15402
@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroSaleBundle:QuoteProductFixture.yml
Feature: Quote translations
  In order to have possibility to create quote in any localization
  As a Buyer
  I want to have all labels to be localized

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations

  Scenario: Change translation for Note label
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And go to System/Localization/Translations
    And filter Translated Value as is empty
    And filter Translation as is equal to "Note"
    And I edit "oro.rfp.requestproductitem.note.label" Translated Value as "Note - Zulu"
    And I should see following records in grid:
      | Note - Zulu |

  Scenario: Create request for quote with empty and zero price as buyer
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "Zulu" localization
    And I follow "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I click "Add a Note to This Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | SKU      | psku1     |
      | Quantity | 1         |
      | Note     | Some Note |
    And click "Update Line Item"
    Then should see "Note - Zulu: Some Note"
