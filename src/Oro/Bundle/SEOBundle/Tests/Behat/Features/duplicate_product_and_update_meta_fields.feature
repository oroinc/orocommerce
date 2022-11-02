@regression
@ticket-BB-14588
@fixture-OroSEOBundle:ProductDuplicateFixture.yml
Feature: Duplicate product and update meta fields
  In order to manage products
  As administrator
  I need to be able to save and duplicate product and all meta fields should be cloned
  Any changes of this copy should not affect the original product

  Scenario: Open original product and duplicate it
    Given I login as administrator
    And I go to Products/ Products
    And I click View Product1 in grid
    When I click "Duplicate"
    Then I should see product with:
      | SKU              | PSKU1-1            |
      | Name             | Product1           |
      | Meta Title       | Meta Title 1       |
      | Meta Description | Meta Description 1 |
      | Meta Keywords    | Meta Keywords 1    |

  Scenario: Edit copied product
    Given I click "Edit"
    When fill "Product With Meta Fields Form" with:
      | SKU              | PSKU2              |
      | Name             | Product2           |
      | Meta Title       | Meta Title 2       |
      | Meta Description | Meta Description 2 |
      | Meta Keywords    | Meta Keywords 2    |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see product with:
      | SKU              | PSKU2              |
      | Name             | Product2           |
      | Meta Title       | Meta Title 2       |
      | Meta Description | Meta Description 2 |
      | Meta Keywords    | Meta Keywords 2    |

  Scenario: Verify that original product is not changed
    And I go to Products/ Products
    When I click View Product1 in grid
    And I should see product with:
      | SKU              | PSKU1              |
      | Name             | Product1           |
      | Meta Title       | Meta Title 1       |
      | Meta Description | Meta Description 1 |
      | Meta Keywords    | Meta Keywords 1    |
