@regression
@ticket-BB-18879
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attributes order
  In order to have custom attributes properly ordered for Product entity
  As an Administrator
  I need to be able to save product family with the specified order of attributes

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attributes
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | Document |
      | Type       | File     |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | Drawing |
      | Type       | Image   |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | Thumbnail Width   | 100                 |
      | Thumbnail Height  | 100                 |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | MultipleImages  |
      | Type       | Multiple Images |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | Thumbnail Width   | 64                  |
      | Thumbnail Height  | 64                  |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | MultipleFiles  |
      | Type       | Multiple Files |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Document, Drawing, MultipleFiles, MultipleImages] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check fields order
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    Then I should see "Drawing Field" goes after "Document Field"
    And I should see "MultipleImages Field" goes after "MultipleFiles Field"
    When I fill "Product Form" with:
      | Document         | example.pdf  |
      | Drawing          | cat1.jpg     |
      | MultipleFiles 1  | example2.pdf |
      | MultipleImages 1 | cat2.jpg     |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Drawing Backoffice Field" goes after "Document Backoffice Field"
    And I should see "MultipleImages Field" goes after "MultipleFiles Field"

  Scenario: Check attributes order on store front
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    Then I should see "Drawing Storefront Field" goes after "Document Storefront Field"
    And I should see "MultipleImages Storefront Field" goes after "MultipleFiles Storefront Field"

  Scenario: Change order of attributes
    Given I proceed as the Admin
    And I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Drawing, Document, MultipleImages, MultipleFiles] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check fields orders, update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    Then I should see "Document Field" goes after "Drawing Field"
    And I should see "MultipleFiles Field" goes after "MultipleImages Field"
    When I save and close form
    Then I should see "Document Backoffice Field" goes after "Drawing Backoffice Field"
    And I should see "MultipleFiles Field" goes after "MultipleImages Field"

  Scenario: Check attributes order on store front
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Document Storefront Field" goes after "Drawing Storefront Field"
    And I should see "MultipleFiles Storefront Field" goes after "MultipleImages Storefront Field"
