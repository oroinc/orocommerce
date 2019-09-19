@regression
@ticket-BAP-19152

Feature: Many to many product entity extend relation
  In order to allow users to link entities to product entity via extend relations
  As an Administrator
  I want to create extend many to many relation for product entity

  Scenario: Feature Background
    Given I login as administrator
    And go to System/Entities/Entity Management

  Scenario: Can create Many to many extend relation field
    Given I filter Name as is equal to "Product"
    And click View Product in grid
    And click "Create Field"
    And fill form with:
      | Field name | productTemplates |
      | Type       | Many to many     |
    And click "Continue"
    When I fill form with:
      | Target entity              | Email Template |
      | Related entity data fields | Id             |
      | Related entity info title  | [Subject]      |
      | Related entity detailed    | [Content]      |
    And I save and close form
    Then I should see "Field saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Populate created relation field for a newly created entity
    Given go to Products / Products
    And I click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "All Products"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Simple_1 |
      | Name             | Simple_1 |
      | Status           | Enable   |
      | Unit Of Quantity | item     |
    And I click "Add Product Templates Button"
    And I select following records in grid:
      | OAuth application added to your account |
      | Deactivation Notice                     |
      | Please change your password             |
    And I click "Select"
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Simple_1"
    And I should see "OAuth application added to your account"
    And I should see "Deactivation Notice"
    And I should see "Please change your password"
