@regression
@ticket-BB-23017

Feature: Attributes and fields of type multiple are duplicated with the product
  As an administrator, I want to be able to duplicate a product with fields of type "multiple"

  Scenario: Authenticate
    Given I login as administrator
    And go to Products / Product Attributes

  Scenario Outline: Create Multiple Files attributes
    Given I click "Create Attribute"
    When I fill form with:
      | Field name | <Name>         |
      | Type       | Multiple Files |
    And click "Continue"
    And fill form with:
      | Label             | <Name>       |
      | Stored Externally | <Externally> |
      | File Size (MB)    | 10           |
      | Use DAM           | <Use DAM>    |
      | File Applications | [default]    |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Name                         | Externally | Use DAM |
      | multiple_files_attribute     | No         | No      |
      | multiple_files_dam_attribute | No         | Yes     |

  Scenario Outline: Create Multiple Images attribute
    Given I click "Create Attribute"
    When I fill form with:
      | Field name | <Name>          |
      | Type       | Multiple Images |
    And click "Continue"
    And fill form with:
      | Label             | <Name>       |
      | Stored Externally | <Externally> |
      | File Size (MB)    | 10           |
      | Thumbnail Width   | 1024         |
      | Thumbnail Height  | 1024         |
      | Use DAM           | <Use DAM>    |
      | File Applications | [default]    |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Name                          | Externally | Use DAM |
      | multiple_images_attribute     | No         | No      |
      | multiple_images_dam_attribute | No         | Yes     |

  Scenario: Create fields
    Given I go to System/ Entities/ Entity Management
    And I filter "Name" as is equal to "Product"
    And I click view Product in grid

  Scenario Outline: Create Multiple Files fields
    Given I click "Create field"
    When I fill form with:
      | Field name   | <Name>         |
      | Storage type | Table column   |
      | Type         | Multiple Files |
    And click "Continue"
    And fill form with:
      | Label             | <Name>       |
      | Stored Externally | <Externally> |
      | File Size (MB)    | 10           |
      | Use DAM           | <Use DAM>    |
      | File Applications | [default]    |
    And save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Name                     | Externally | Use DAM |
      | multiple_files_field     | No         | No      |
      | multiple_files_dam_field | No         | Yes     |

  Scenario Outline: Create Multiple Images fields
    Given I click "Create field"
    When I fill form with:
      | Field name | <Name>          |
      | Type       | Multiple Images |
    And click "Continue"
    And fill form with:
      | Label             | <Name>       |
      | Stored Externally | <Externally> |
      | File Size (MB)    | 10           |
      | Thumbnail Width   | 1024         |
      | Thumbnail Height  | 1024         |
      | Use DAM           | <Use DAM>    |
      | File Applications | [default]    |
    And save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Name                      | Externally | Use DAM |
      | multiple_images_field     | No         | No      |
      | multiple_images_dam_field | No         | Yes     |

  Scenario: Update schema
    Given I click update schema
    Then I should see Schema updated flash message

  Scenario: Add attributes to default family
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And fill "Product Family Form" with:
      | General Attributes | [multiple_files_attribute, multiple_files_dam_attribute, multiple_images_attribute, multiple_images_dam_attribute] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Create and duplicate simple product with empty attribute data
    Given I go to Products/ Products
    When I click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And click "Continue"
    And fill "ProductForm" with:
      | Sku  | ORO_PRODUCT_WITH_EMPTY_DATA |
      | Name | ORO_PRODUCT_WITH_EMPTY_DATA |
    And save and close form
    Then I should see "Product has been saved" flash message
    When I click "Duplicate"
    Then I should see "Product has been duplicated" flash message

  Scenario: Duplicate simple product with attribute data
    Given I go to Products/ Products
    When I click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And click "Continue"
    And fill "ProductForm" with:
      | Sku                       | ORO_PRODUCT  |
      | Name                      | ORO_PRODUCT  |
      | Multiple Files Attribute  | example.pdf  |
      | Multiple Images Attribute | 300x300.png  |
      | Multiple Files Field      | example2.pdf |
      | Multiple Images Field     | blue-dot.jpg |

    And click "Multiple Files DAM Attribute"
    And fill "Digital Asset Dialog Form" with:
      | File  | example.pdf                  |
      | Title | Multiple Files DAM Attribute |
    And click "Upload"
    And click on example.pdf in grid

    And click "Multiple Images DAM Attribute"
    And fill "Digital Asset Dialog Form" with:
      | File  | 300x300.png                   |
      | Title | Multiple Images DAM Attribute |
    And click "Upload"
    And click on 300x300.png in grid

    And click "Multiple Files DAM Field"
    And fill "Digital Asset Dialog Form" with:
      | File  | example2.pdf             |
      | Title | Multiple Files DAM Field |
    And click "Upload"
    And click on example2.pdf in grid

    And click "Multiple Images DAM Field"
    And fill "Digital Asset Dialog Form" with:
      | File  | blue-dot.jpg              |
      | Title | Multiple Images DAM Field |
    And click "Upload"
    And click on blue-dot.jpg in grid

    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Duplicate product
    Given I go to Products/ Products
    And click view "ORO_PRODUCT" in grid
    Then I should see product with:
      | SKU | ORO_PRODUCT |
    And should see following "Multiple Files Attribute Grid" grid:
      | File Name   |
      | example.pdf |
    And should see following "Multiple Images Attribute Grid" grid:
      | Name        |
      | 300x300.png |
    And should see following "Multiple Files Field Grid" grid:
      | File Name    |
      | example2.pdf |
    And should see following "Multiple Images Field Grid" grid:
      | Name         |
      | blue-dot.jpg |
    And should see following "Multiple Files DAM Attribute Grid" grid:
      | File Name   |
      | example.pdf |
    And should see following "Multiple Images DAM Attribute Grid" grid:
      | Name        |
      | 300x300.png |
    And should see following "Multiple Files DAM Field Grid" grid:
      | File Name    |
      | example2.pdf |
    And should see following "Multiple Images DAM Field Grid" grid:
      | Name         |
      | blue-dot.jpg |

    When I click "Duplicate"

    Then I should see product with:
      | SKU | ORO_PRODUCT-1 |
    And should see following "Multiple Files Attribute Grid" grid:
      | File Name   |
      | example.pdf |
    And should see following "Multiple Images Attribute Grid" grid:
      | Name        |
      | 300x300.png |
    And should see following "Multiple Files Field Grid" grid:
      | File Name    |
      | example2.pdf |
    And should see following "Multiple Images Field Grid" grid:
      | Name         |
      | blue-dot.jpg |
    And should see following "Multiple Files DAM Attribute Grid" grid:
      | File Name   |
      | example.pdf |
    And should see following "Multiple Images DAM Attribute Grid" grid:
      | Name        |
      | 300x300.png |
    And should see following "Multiple Files DAM Field Grid" grid:
      | File Name    |
      | example2.pdf |
    And should see following "Multiple Images DAM Field Grid" grid:
      | Name         |
      | blue-dot.jpg |
