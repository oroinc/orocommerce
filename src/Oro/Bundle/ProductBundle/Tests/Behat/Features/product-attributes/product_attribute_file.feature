@regression
@behat-test-env
@ticket-BB-9989
@ticket-BB-7152
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute file
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | FileField |
      | Type       | File      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" does not contain "Searchable"
    And I should see that "Product Attribute Frontend Options" does not contain "Filterable"
    And I should see that "Product Attribute Frontend Options" does not contain "Sortable"
    And I should see "Allowed MIME Types" with options:
      | Value                                                                     |
      | text/csv                                                                  |
      | text/plain                                                                |
      | application/msword                                                        |
      | application/vnd.openxmlformats-officedocument.wordprocessingml.document   |
      | application/vnd.ms-excel                                                  |
      | application/vnd.openxmlformats-officedocument.spreadsheetml.sheet         |
      | application/vnd.ms-powerpoint                                             |
      | application/vnd.openxmlformats-officedocument.presentationml.presentation |
      | application/pdf                                                           |
      | application/zip                                                           |
      | image/gif                                                                 |
      | image/jpeg                                                                |
      | image/png                                                                 |
      | image/svg                                                                 |
      | image/svg+xml                                                             |
    When I fill form with:
      | File Size (MB)     | 10                                       |
      | Allowed MIME types | [application/pdf, image/png, image/jpeg] |
      | File applications  | [default, commerce]                      |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [FileField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | FileField | tiger.svg |
    And I save and close form
    Then I should see "Product Form" validation errors:
      | FileField | The MIME type of the file is invalid ("image/svg+xml"). Allowed MIME types are "application/pdf", "image/png", "image/jpeg". |
    Then I fill "Product Form" with:
      | FileField | cat1.jpg |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check file attribute is available at store front
    Given I login as AmandaRCole@example.org buyer
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    Then I should see "cat1.jpg" link with the url matches "/attachment/.+?\.jpg"
    And I should not see "cat1.jpg" link with the url matches "/admin/"

  Scenario: Remove new attribute from product family
    Given I login as administrator
    And I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is not present
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should not see "FileField"
    And I should not see "cat1.jpg"

  Scenario: Update product family with new attribute again to check if attribute data is deleted
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [FileField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is present but its data is gone
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should see product with:
      | FileField | N/A |

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "FileField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
