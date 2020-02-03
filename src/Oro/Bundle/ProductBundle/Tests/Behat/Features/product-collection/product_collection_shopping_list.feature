@regression
@fixture-OroProductBundle:product_collection_shopping_list.yml
Feature: Product collection shopping list
  In order to edit content node
  As an Buyer
  I want to have ability of editing Shopping list from Product Collection view

  Scenario: Check name of product in collection grid "In shopping List" dialog widget
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "New Category"
    And click "Add to Shopping List" for "PSKU1" product
    And click "Add to Shopping List" for "PSKU2" product
    And click "In Shopping List" for "PSKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product 1 |
    And I click "Close" in modal window
    And click "In Shopping List" for "PSKU2" product
    Then I should see "UiDialog" with elements:
      | Title | Product 2 |
