@ticket-BB-18452
@regression
@elasticsearch
@fixture-OroProductBundle:product_short_description_localization.yml
Feature: Product short description localization
  In order to have localized product short description with using wysiwyg editor
  As a Buyer
  I need to be able to see localized product short description with correct html formatting

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I enable the existing localizations

  Scenario: Edit short description
    Given I go to Products / Products
    And I click Edit SKU1 in grid
    And I click "Localization1"
    And I click "Default Value"
    And fill "ProductForm" with:
      | Short Description | <strong>Edited default localization</strong> |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check that product description is displayed properly
    Given I proceed as the Buyer
    And I am on homepage
    When type "SKU1" in "search"
    And I click "Search Button"
    Then I should see "Edited default localization" in the "Short Description With Strong Tag" element

  Scenario: Localization 1 not affected
    When I click "Localization Switcher"
    And I select "Localization 1" localization
    Then I should see "Short description for localization 1" in the "Short Description With Strong Tag" element
