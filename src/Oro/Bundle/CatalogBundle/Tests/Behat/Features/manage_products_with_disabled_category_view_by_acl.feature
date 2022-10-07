@regression
@ticket-BB-21483
@fixture-OroUserBundle:manager.yml
@fixture-OroProductBundle:product_check_category.yml
Feature: Manage products with disabled category view by ACL
  In order to have the ability to manage view access to categories
  As an Administrator
  I want to manage view ACL by role for entity "Category"
  As a Manager
  I want to have the ability to manage products even in the case when I have no right to view "Category"

  Scenario: Edit product when view "Category" enabled
    Given I login as administrator
    And I go to Products/ Products
    And I wait for action
    And I click edit "PSKU1" in grid
    Then I should see "Master Catalog"
    When fill "ProductForm" with:
      | Status | Disabled |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Category"

  Scenario: Create product when view "Category" enabled
    Given I go to Products/ Products
    And click "Create Product"
    Then I should see "Master Catalog"
    And I click "Continue"
    And fill "Create Product Form" with:
      | SKU         | SKU_NEW_WITH_CAT_ENABLED      |
      | Name        | Test Product With Cat Enabled |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Change Manager view "Category" permission
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Category | View:None |
    And I save and close form
    Then I should see "Role saved" flash message

  Scenario: Edit product when view "Category" enabled
    Given I go to Products/ Products
    And I click edit "PSKU1" in grid
    Then I should not see "Master Catalog"
    When fill "ProductForm" with:
      | Status | Enabled |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should not see "Category"

  Scenario: Create product when view "Category" enabled
    Given I go to Products/ Products
    And click "Create Product"
    Then I should not see "Master Catalog"
    And I click "Continue"
    And fill "Create Product Form" with:
      | SKU         | SKU_NEW_WITH_CAT_DISABLED      |
      | Name        | Test Product With Cat Disabled |
    And I save form
    Then I should see "Product has been saved" flash message
