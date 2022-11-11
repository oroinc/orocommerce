@ticket-BB-20452
Feature: Edit product with deleted custom image field
  In order to have correct product when we have deleted new image field and update schema
  As administrator
  I need to be able to create new field for some entity, delete this field and update schema and have correct product entity

  Scenario: Delete created image field and update schema
    Given I login as administrator
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    And click View Product in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name | dummy_image |
      | Type       | Image       |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)     | 1                       |
      | Thumbnail Width    | 100                     |
      | Thumbnail Height   | 100                     |
      | Allowed MIME types | [image/jpeg, image/png] |
      | Use DAM            | No                      |
    And I save and close form
    Then I should see "Update schema"
    When I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
    When filter Name as is equal to "Product"
    And click View Product in grid
    And I click remove "dummy_image" in grid
    And click "Yes"
    Then I should see "Update schema"
    When I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message

  Scenario: Create/edit product with deleted image field without errors
    When I go to Products/Products
    And click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU               | Test1234                           |
      | Name              | Test Product 1                     |
      | Status            | Enable                             |
      | Unit Of Quantity  | item                               |
      | Short Description | short_product_description_test1234 |
      | Description       | full_product_description_test1234  |
    And I save and close form
    Then I should see product with:
      | SKU | Test1234 |
    When I click "Edit"
    And fill "ProductForm" with:
      | SKU | Test1234567 |
    And I save and close form
    Then I should see product with:
      | SKU | Test1234567 |
