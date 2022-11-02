@ticket-BB-19268
@fixture-OroProductBundle:product_collection_add_title_translate.yml
Feature: Product collection add title translate
  In order to added new title translate
  As an Administrator
  I want to have ability to added new title translation to content node with product collection content variant

  Scenario: Content node with Product Collection can be changed titles
    Given I login as administrator
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Product Collection Node"
    When I click on "Content Node Titles Localization Form Fallbacks"
    And fill "Content Node Titles Localization Form" with:
      | Default Value           | New Default Title             |
      | Use Default             | false                         |
      | English (United States) | English (United States) Title |
    And I save form
    Then I should see "Content Node has been saved" flash message
