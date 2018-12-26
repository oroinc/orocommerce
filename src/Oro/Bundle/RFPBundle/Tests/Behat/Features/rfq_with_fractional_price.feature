@ticket-BB-14800
@fixture-OroLocaleBundle:GermanLocalization.yml
@fixture-OroShoppingListBundle:ShoppingListWithFractionalPriceFixture.yml

Feature: RFQ with fractional price
  In order to use correct decimal separator for fractional prices in different locales
  As an Buyer
    I should see fractional prices formatted according locale settings in the process of RFQ.
    Also I should offer new fractional price using decimal delimiter according locale settings.

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I enable the existing localizations

  Scenario: Create RFQ with fractional prices from shopping list
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "German Localization" localization

    When I open page with shopping list "Shopping List 1"
    And I click "Request Quote"
    And I fill form with:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Company       | first customer          |
    And click "Edit RFQ Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | SKU          | PSKU1  |
      | Quantity     | 20     |
      | Target Price | 10,99  |
    And click "Update Line Item"
    Then I should see "Product 1 QTY: 20 each Target Price 10,99 $ Listed Price: 12,99 $"

    When I click "Submit Request"
    Then I should see RFQ with data:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Company       | first customer          |
