@fixture-OroProductBundle:products_grid.yml
@ticket-BAP-21966
@fixture-OroTagBundle:TagFixture.yml
Feature: Tags with product
  As an Administrator
  I want to be able to manage tags on products grid and on product view

  Scenario: Manage tags on products grid
    Given I login as administrator
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    And click Edit Product in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

    Then I go to Products/ Products
    Then I should see "Tags" column in grid
    When I edit "PSKU8" Tags as "Wholesale" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message
    Then I should see "Wholesale" in grid

  Scenario: Manage tags on product
    When I click view "PSKU9" in grid
    And I click "General"
    And I should see "Tags" in the "Product General Section" element
    Then I go to Products/ Products
    When I click view "PSKU8" in grid
    And I press "Edit Tags Button"
    And fill "View Page Tags Form" with:
      | Tags | [Service] |
    And submit form
    Then I should see "Record has been successfully updated" flash message
