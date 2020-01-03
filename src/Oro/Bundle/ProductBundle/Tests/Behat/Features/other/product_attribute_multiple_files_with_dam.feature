@ticket-BB-18228
@regression

Feature: Product Attribute Multiple Files with DAM
  In order to create product with attribute Multiple Files
  As an administrator
  I should be able to create attribute with type Multiple Files with Digital Asset Manager

  Scenario: Create Multiple Files attribute
    Given I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field name    | custom_files   |
      | Type          | Multiple Files |
    And I click "Continue"
    And I fill form with:
      | Label                   | Custom Files        |
      | File Size (MB)          | 10                  |
      | Maximum Number Of Files | 2                   |
      | Use DAM                 | Yes                 |
      | File Applications       | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add Multiple Files attribute to Default product family
    Given I go to Products / Product Families
    And I click Edit Default in grid
    When set Attribute Groups with:
      | Label            | Visible | Attributes     |
      | Additional Files | true    | [Custom Files] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Create simple product
    When I go to Products/ Products
    And I click "Create Product"
    And I fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And I click "Continue"
    And I fill "ProductForm" with:
      | Sku  | test_product |
      | Name | Test Product |

  Scenario: Attach existing files and save product form
    Given I click "Choose File 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | example.pdf |
      | Title | Example PDF |
    And I click "Upload"
    And I click on example.pdf in grid
    And I click "Add File"
    And I click "Choose File 2"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg   |
      | Title | Cat 1 JPEG |
    And I click "Upload"
    And I click on cat1.jpg in grid
    Then I should not see "Add File"
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see following grid:
      | Sort Order | File name   | Uploaded By |
      | 1          | example.pdf | John Doe    |
      | 2          | cat1.jpg    | John Doe    |
