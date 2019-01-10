@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute image
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | ImageField |
      | Type       | Image      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" does not contain "Searchable"
    And I should see that "Product Attribute Frontend Options" does not contain "Filterable"
    And I should see that "Product Attribute Frontend Options" does not contain "Sortable"

    When I fill form with:
      | File Size        | 10   |
      | Thumbnail Width  | 1900 |
      | Thumbnail Height | 1200 |
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [ImageField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | ImageField | cat1.jpg |
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg | 1     | 1       | 1          |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product image zoom in additional tabs
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And fill "Product Family Form" with:
      | Attribute Groups Attributes SEO | [Images] |
      | Attribute Groups Visible SEO    | true     |
    And I save and close form
    Then I should see "Successfully updated" flash message
    And I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    And I click "Copyright"
    And I hover on "Product Main Image"
    And I check element "Zoom Container" has width "564"

  Scenario: Delete product attribute
    Given I proceed as the Admin
    And I go to Products/ Product Attributes
    When I click Remove "ImageField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
