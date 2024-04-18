@regression
@ticket-BB-22832
@ticket-BB-23763

Feature: Check that tabs for multiple file and multiple images fields are displayed correctly on product pages
  As an administrator, I want to make sure that:
  - entity fields are not displayed on the first step of product creation page and not on the product
  visibility page.
  - entity entity fields are displayed on product second step creation page, product edit page, product view page.
  - product attributes fields are not displayed on the first step of product creation page, second step of product
  creation page, product edit page, product view page, product visibility page if attributes not added to family.
  - product attributes fields are not displayed on the first step of product creation page, product visibility
  page if attributes added to family.
  - product attributes are displayed on product second step creation page, product edit page, product view page
  if attributes added to family.

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
      | Label             | <Label>   |
      | Stored Externally | No        |
      | File Size (MB)    | 10        |
      | Use DAM           | <Use DAM> |
      | File Applications | [default] |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Name                         | Label                        | Use DAM |
      | multiple_files_attribute     | Multiple files attribute     | No      |
      | multiple_files_dam_attribute | Multiple files dam attribute | Yes     |

  Scenario Outline: Create Multiple Images attribute
    Given I click "Create Attribute"
    When I fill form with:
      | Field name | <Name>          |
      | Type       | Multiple Images |
    And click "Continue"
    And fill form with:
      | Label             | <Label>   |
      | Stored Externally | No        |
      | File Size (MB)    | 10        |
      | Thumbnail Width   | 1024      |
      | Thumbnail Height  | 1024      |
      | Use DAM           | <Use DAM> |
      | File Applications | [default] |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    Examples:
      | Name                          | Label                         | Use DAM |
      | multiple_images_attribute     | Multiple images attribute     | No      |
      | multiple_images_dam_attribute | Multiple images dam attribute | Yes     |

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
      | Label             | <Label>   |
      | Stored Externally | No        |
      | File Size (MB)    | 10        |
      | Use DAM           | <Use DAM> |
      | File Applications | [default] |
    And save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Name                     | Label                    | Use DAM |
      | multiple_files_field     | Multiple files field     | No      |
      | multiple_files_dam_field | Multiple files dam field | Yes     |

  Scenario Outline: Create Multiple Images fields
    Given I click "Create field"
    When I fill form with:
      | Field name | <Name>          |
      | Type       | Multiple Images |
    And click "Continue"
    And fill form with:
      | Label             | <Label>   |
      | Stored Externally | No        |
      | File Size (MB)    | 10        |
      | Thumbnail Width   | 1024      |
      | Thumbnail Height  | 1024      |
      | Use DAM           | <Use DAM> |
      | File Applications | [default] |
    And save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Name                      | Label                     | Use DAM |
      | multiple_images_field     | Multiple images field     | No      |
      | multiple_images_dam_field | Multiple images dam field | Yes     |

  Scenario: Update schema
    Given I click update schema
    Then I should see Schema updated flash message

  Scenario: Check for fields and missing attributes on product creation pages
    Given I go to Products/ Products
    When I click "Create Product"
    Then I should see the following tabs on product page:
      | General        |
      | Product Family |
      | Master Catalog |
    # At the first step of creating a product, we should not see additional fields and attributes.
    # Check product attribute and extend fields on first product create page.
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
      | Multiple files field          |
      | Multiple files dam field      |
      | Multiple images field         |
      | Multiple images dam field     |
      | Multiple images dam field     |
    When I fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And click "Continue"
    # Attributes is not displayed because they are not added to the product family.
    # Check product attribute on second product create page.
    Then I should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on second product create page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    When I fill "ProductForm" with:
      | Sku  | ORO_PRODUCT_1 |
      | Name | ORO_PRODUCT_1 |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check for fields and missing attributes on product edit page
    Given I click "Edit"
    # Check product attribute on product edit page
    Then I should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    And should not see "Multiple Files Attribute"
    And should not see "Multiple Files DAM Attribute"
    And should not see "Multiple Images Attribute"
    And should not see "Multiple Images DAM Attribute"
    # Check extend field on product edit page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should see "Multiple Files Field"
    And should see "Multiple Files DAM Field"
    And should see "Multiple Images Field"
    And should see "Multiple Images DAM Field"
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check for fields and missing attributes on product view page
    # Check product attribute on product view page
    Given I should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    And should not see an "Multiple Files Attribute Label" element
    And should not see an "Multiple Files DAM Attribute Label" element
    And should not see an "Multiple Images Attribute Label" element
    And should not see an "Multiple Images DAM Attribute Label" element
    # Check extend field on product view page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should see a "Multiple Files Field Label" element
    And should see a "Multiple Files DAM Field Label" element
    And should see a "Multiple Images Field Label" element
    And should see a "Multiple Images DAM Field Label" element

  Scenario: Check for fields and missing attributes on product visibility page
    Given I click "More actions"
    When I click "Manage Visibility"
    # Check product attribute on product visibility page
    Then I should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on product visibility page
    And should not see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should not see "Multiple Files Attribute"
    And should not see "Multiple Files DAM Attribute"
    And should not see "Multiple Images Attribute"
    And should not see "Multiple Images DAM Attribute"
    And should not see "Multiple Files Field"
    And should not see "Multiple Files DAM Field"
    And should not see "Multiple Images Field"
    And should not see "Multiple Images DAM Field"

  Scenario: Update product family with new attributes
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And click "Add"
    And fill "Attributes Group Form" with:
      | Attribute Groups Label0      | Multiple Fields Attributes                                                                                         |
      | Attribute Groups Visible0    | true                                                                                                               |
      | Attribute Groups Attributes0 | [Multiple files attribute, Multiple files dam attribute, Multiple images attribute, Multiple images dam attribute] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check for fields and attributes on product creation pages
    Given I go to Products/ Products
    When I click "Create Product"
    # Check product attribute on first product create page
    Then I should not see the following tabs on product page:
      | Multiple Fields Attributes |
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on first product create page
    And should not see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    When I fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And I click "Continue"
    # Check product attribute on second product create page
    Then I should see the following tabs on product page:
      | Multiple Fields Attributes |
    # Check if the attributes are displayed (attributes fields do not have names except for family tab)
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on second product create page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should see "Multiple files attribute"
    And should see "Multiple files dam attribute"
    And should see "Multiple images attribute"
    And should see "Multiple images dam attribute"
    And should see "Multiple files field"
    And should see "Multiple files dam field"
    And should see "Multiple images field"
    And should see "Multiple images dam field"
    When I fill "ProductForm" with:
      | Sku  | ORO_PRODUCT_2 |
      | Name | ORO_PRODUCT_2 |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check for fields and attributes on product edit page
    Given I click "Edit"
    # Check product attribute on product edit page
    Then I should see the following tabs on product page:
      | Multiple Fields Attributes |
    # Check if the attributes are displayed (attributes fields do not have names except for family tab)
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on product edit page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should see "Multiple files attribute"
    And should see "Multiple files dam attribute"
    And should see "Multiple images attribute"
    And should see "Multiple images dam attribute"
    And should see "Multiple files field"
    And should see "Multiple files dam field"
    And should see "Multiple images field"
    And should see "Multiple images dam field"
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check for fields and attributes on product view page
    # Check product attribute on product view page
    Given I should see the following tabs on product page:
      | Multiple Fields Attributes |
    # Check if the attributes are displayed (attributes fields do not have names except for family tab)
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on product view page
    And should see the following tabs on product page:
      | Multiple files field      |
      | Multiple files dam field  |
      | Multiple images field     |
      | Multiple images dam field |
    And should see a "Multiple Files Attribute Label" element
    And should see a "Multiple Files DAM Attribute Label" element
    And should see a "Multiple Images Attribute Label" element
    And should see a "Multiple Images DAM Attribute Label" element
    And should see a "Multiple Files Field Label" element
    And should see a "Multiple Files DAM Field Label" element
    And should see a "Multiple Images Field Label" element
    And should see a "Multiple Images DAM Field Label" element

  Scenario: Check for fields and attributes on product visibility page
    Given I click "More actions"
    When I click "Manage Visibility"
    # Check product attribute on product visibility page
    Then I should not see the following tabs on product page:
      | Multiple Fields Attributes |
    And should not see the following tabs on product page:
      | Multiple files attribute      |
      | Multiple files dam attribute  |
      | Multiple images attribute     |
      | Multiple images dam attribute |
    # Check extend field on product visibility page
    And should not see the following tabs on product page:
      | multiple_files_field      |
      | multiple_files_dam_field  |
      | multiple_images_field     |
      | multiple_images_dam_field |
    And should not see "multiple_files_attribute"
    And should not see "multiple_files_dam_attribute"
    And should not see "multiple_images_attribute"
    And should not see "multiple_images_dam_attribute"
    # Check extend field on product visibility page
    And should not see "multiple_files_field"
    And should not see "multiple_files_dam_field"
    And should not see "multiple_images_field"
    And should not see "multiple_images_dam_field"

  Scenario: Create product and check fields and attributes
    Given I go to Products/ Products
    When I click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And click "Continue"
    And fill "ProductForm" with:
      | Sku                       | ORO_PRODUCT_3 |
      | Name                      | ORO_PRODUCT_3 |
      | Multiple Files Attribute  | example.pdf   |
      | Multiple Images Attribute | 300x300.png   |
      | Multiple Files Field      | example2.pdf  |
      | Multiple Images Field     | blue-dot.jpg  |

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
