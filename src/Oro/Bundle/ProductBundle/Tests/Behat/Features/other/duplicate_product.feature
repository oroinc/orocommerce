@regression
@ticket-BB-10111
@fixture-OroProductBundle:ProductDuplicateFixture.yml
Feature: Duplicate product
  In order to manage products
  As administrator
  I need to be able to save and duplicate product

  Scenario: Duplicate Product
    Given I login as administrator
    When I go to Products/ Products
    And number of records should be 1
    And I should see PSKU1 in grid with following data:
      | Name             | Product1 |
      | Inventory Status | In Stock |
      | Status           | Enabled  |
    And I click Edit Product1 in grid
    And I save and duplicate form
    Then I should see "Product has been saved and duplicated" flash message

  Scenario: Verify Duplication
    Given I go to Products/ Products
    When number of records should be 2
    Then I should see PSKU1-1 in grid with following data:
      | Name             | Product1 |
      | Inventory Status | In Stock |
      #By default duplicated product should be disabled
      | Status           | Disabled |
    And filter SKU as is equal to "PSKU1"
    And I should see PSKU1 in grid with following data:
      | Name             | Product1 |
      | Inventory Status | In Stock |
      | Status           | Enabled  |
