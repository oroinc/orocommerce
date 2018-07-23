@regression
@ticket-BB-14588
@fixture-OroSEOBundle:ProductDuplicateFixture.yml
Feature: Duplicate product and update meta fields
  In order to manage products
  As administrator
  I need to be able to save and duplicate product and all meta fields should be cloned

  Scenario: Duplicate Product
    Given I login as administrator
    When I go to Products/ Products
    And number of records should be 1
    And I should see PSKU1 in grid with following data:
      | Name             | Product 1 |
      | Status           | Enabled  |
    And I click Edit Product 1 in grid
    And I save and duplicate form
    Then I should see "Product has been saved and duplicated" flash message

  Scenario: Verify duplicated product
    Given I go to Products/ Products
    When number of records should be 2
    Then I should see PSKU1-1 in grid with following data:
      | Name             | Product 1 |
      | Inventory Status | In Stock |
      | Status           | Disabled |
    When I click View "PSKU1-1" in grid
    Then I should see "Meta Title 1"
    And I should see "Meta Description 1"
    And I should see "Meta Keyword 1"

  Scenario: Edit meta fields for duplicated product
    Given I press "Edit"
    And I fill in "ProductMetaTitleField" with "Meta Title 2"
    And I fill in "ProductMetaDescriptionField" with "Meta Description 2"
    And I fill in "ProductMetaKeywordField" with "Meta Keyword 2"
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Meta Title 2"
    And I should see "Meta Description 2"
    And I should see "Meta Keyword 2"

  Scenario: Verify source product meta fields
    Given I am on homepage
    And I open product with sku "PSKU1" on the store frontend
    Then Page meta title equals "Meta Title 1"
    And Page meta description equals "Meta Description 1"
    And Page meta keywords equals "Meta Keyword 1"
