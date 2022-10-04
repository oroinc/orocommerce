@ticket-BAP-21510

Feature: Product brand
  In order to create and update brand
  As an Administrator
  I want to be able to create and update brands for the product

  Scenario: Create Brand
    Given I login as administrator
    When I go to Products/ Product Brands
    Then Page title equals to "All - Product Brands - Products"
    And I should see "Products / Product Brands" in breadcrumbs
    When click "Create Brand"
    Then Page title equals to "Create Brand - Product Brands - Products"
    And I should see "Products / Product Brands" in breadcrumbs
    When fill "Brand Form" with:
      | Name              | New brand           |
      | Status            | Enable              |
      | Description       | Default description |
      | Short Description | Short description   |
    When save and close form
    Then I should see "Brand has been saved" flash message

  Scenario: Update Brand
    When I click "Edit" on row "New brand" in grid
    When fill "Brand Form" with:
      | Name              | New brand update           |
      | Description       | Default description update |
      | Short Description | Short description update   |
    And save and close form
    Then I should see "Brand has been saved" flash message

