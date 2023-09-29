@ticket-BB-21424
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attribute import wysiwyg
  In order to have custom wysiwyg attributes for Product entity
  As an Administrator
  I need to be able to import product wysiwyg attributes and add to product family without errors

  Scenario: Import product wysiwyg attributes
    Given I login as administrator
    When I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName       | type    | entity.label              | entity.description                | importexport.order | importexport.excluded | frontend.is_displayable | frontend.is_editable | form.is_enabled | attribute.searchable | attribute.filterable | attribute.filter_by | view.is_displayable | attachment.acl_protected |
      | field_wysiwyg_1 | wysiwyg | The_wysiwyg_field_label_1 | The_wysiwyg_field_description_1   | 1                  | Yes                   | Yes                     | No                   | Yes             | Yes                  | Yes                  | exact_value         | Yes                 | Yes                      |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Add product attribute to default product family
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [The_wysiwyg_field_label_1] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check product create without errors with imported wysiwyg attribute
    When I go to Products/ Products
    And I click "Create Product"
    Then I should not see flash messages
    When I click "Continue"
    And fill "ProductForm" with:
      | SKU                             | TestProductWithWysiwygAttribute1                    |
      | Name                            | Test Product With Wysiwyg Attribute 1               |
      | Status                          | Enable                                              |
      | Product WYSIWYG Attribute Field | <a href=\"#\">Link in The_wysiwyg_field_label_1</a> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see product with:
      | SKU  | TestProductWithWysiwygAttribute1      |
      | Name | Test Product With Wysiwyg Attribute 1 |
    And I should see "The_wysiwyg_field_label_1"
    And I should see "Link in The_wysiwyg_field_label_1"

  Scenario: Check product edit without errors with imported wysiwyg attribute
    When I click "Edit Product"
    Then I should not see flash messages
    And fill "ProductForm" with:
      | SKU                             | TestProductWithWysiwygAttribute2                            |
      | Name                            | Test Product With Wysiwyg Attribute 2                       |
      | Status                          | Enable                                                      |
      | Product WYSIWYG Attribute Field | <a href=\"#\">Updated link in The_wysiwyg_field_label_1</a> |
    And I save and close form
    And I click "Apply"
    Then I should see "Product has been saved" flash message
    And I should see product with:
      | SKU  | TestProductWithWysiwygAttribute2      |
      | Name | Test Product With Wysiwyg Attribute 2 |
    And I should see "The_wysiwyg_field_label_1"
    And I should see "Updated link in The_wysiwyg_field_label_1"
