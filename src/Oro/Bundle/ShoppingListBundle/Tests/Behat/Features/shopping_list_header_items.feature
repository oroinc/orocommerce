@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Shopping List Header Items
  In order to manager shopping list on store front
  As a Buyer
  I want to be able to see the correct names of columns in the shopping list

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations

  Scenario: Verify shopping list header items
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open shopping list widget
    And I click "View Details"
    Then I should see following header in shopping list line items table:
      | Item | Price |

  Scenario: Verify translation key for shopping list header item "price"
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Localization/Translations
    When I filter Translated Value as is empty
    And I filter Key as is equal to "oro.frontend.shoppinglist.view.item.lable"
    And I edit "oro.frontend.shoppinglist.view.item.lable" Translated Value as "Item - Zulu"
    Then I should see following records in grid:
      | Item - Zulu |
    When I filter Key as is equal to "oro.frontend.shoppinglist.view.price.label"
    And I edit "oro.frontend.shoppinglist.view.price.label" Translated Value as "Price - Zulu"
    Then I should see following records in grid:
      | Price - Zulu |
    And I click "Update Cache"

  Scenario: Verify shopping list header items for different localization and translation
    Given I proceed as the Buyer
    And I click "Localization Switcher"
    When I select "Zulu" localization
    Then I should see following header in shopping list line items table:
      | Item - Zulu | Price - Zulu |
