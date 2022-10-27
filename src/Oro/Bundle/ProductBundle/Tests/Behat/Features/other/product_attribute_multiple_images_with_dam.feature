@ticket-BB-18228
@regression

Feature: Product Attribute Multiple Images with DAM
  In order to create product with attribute Multiple Images
  As an administrator
  I should be able to create attribute with type Multiple Images with Digital Asset Manager

  Scenario: Create Multiple Images attribute
    Given I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field name    | custom_images   |
      | Type          | Multiple Images |
    And I click "Continue"
    And I save form
    Then I should see validation errors:
      | File Size (MB)   | This value should not be blank. |
      | Thumbnail Width  | This value should not be blank. |
      | Thumbnail Height | This value should not be blank. |
    When I fill form with:
      | Label                   | Custom Images       |
      | File Size (MB)          | 10                  |
      | Thumbnail Width         | 64                  |
      | Thumbnail Height        | 64                  |
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
      | Label            | Visible | Attributes      |
      | Additional Files | true    | [Custom Images] |
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

  Scenario: Attach existing images and save product form
    Given I click "Choose Image 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | Cat 1 JPEG |
    And I click "Upload"
    And I click on cat1.jpg in grid
    And I click "Add Custom Image"
    And I click "Choose Image 2"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg   |
      | Title | Cat 2 JPEG |
    And I click "Upload"
    And I click on cat2.jpg in grid
    Then I should not see "Add Custom Image"
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see following grid:
      | Sort Order | Name     | Uploaded By |
      | 1          | cat1.jpg | John Doe    |
      | 2          | cat2.jpg | John Doe    |
