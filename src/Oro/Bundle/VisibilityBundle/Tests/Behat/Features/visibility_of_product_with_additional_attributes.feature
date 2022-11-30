@ticket-BB-21722
@fixture-OroVisibilityBundle:simple_product.yml
@fixture-OroVisibilityBundle:multi_website.yml

Feature: Visibility of product with additional attributes
  In order to manager product visibility
  As an Administrator
  I want to be able to edit product visibility and additional attributes shouldn't be purged

  Scenario: Add additional attribute for product entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    And click View Product in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name    | additional_name |
      | Type          | String      |
    And I click "Continue"
    When I save and close form
    Then click update schema

  Scenario: Edit product`s additional attribute
    Given I go to Products / Products
    And I click Edit SimpleProductSKU in grid
    And I fill "Product Form" with:
      | additional_name | additional value |
    And I save and close form

  Scenario: Edit product`s visibility
    Given I follow "More actions"
    When I click "Manage Visibility"
    And click "Europe" tab
    And I fill "Visibility Product Form" with:
      | Visibility To All | Hidden |
    And I save and close form
    Then I should see "Product visibility has been saved" flash message
    And I should see "Additional_name additional value"
