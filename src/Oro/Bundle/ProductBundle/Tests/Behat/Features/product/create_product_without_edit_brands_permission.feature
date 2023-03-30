@ticket-BAP-21907
@fixture-OroCatalogBundle:categories.yml
@fixture-OroCustomerBundle:CustomerUserFixture.yml

Feature: Create product without edit brands permission
  In order to manage products
  As user
  I need to be able to create product and select brands at create product form even if I have no access to edit brands

  Scenario: Edit Edit permissions for Brand entity
    Given I login as administrator
    Then I go to System / User Management / Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | Brand | Edit:None |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Add new brand
    Given I go to Products/ Brand
    And click "Create Brand"
    And I fill "Brand Form" with:
      | Name | Test Brand |
    When I save and close form
    Then I should see "Brand has been saved" flash message

  Scenario: Create product with limited access to brands
    Given I login as administrator
    And I go to Products/ Products
    And I click "Create Product"
    And I click "Retail Supplies"
    And I click "Continue"
    And I fill "Create Product Form" with:
      | SKU              | Test123                                    |
      | Name             | Test Product                               |
      | Status           | Enable                                     |
      | Unit Of Quantity | item                                       |
    When I click on "Brand hamburger"
    Then I should see following grid:
      | Brand            |
      | Test Brand       |
    When I click on Test Brand in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
