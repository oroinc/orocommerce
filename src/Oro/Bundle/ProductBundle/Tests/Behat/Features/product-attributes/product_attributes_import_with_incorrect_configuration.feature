@regression
@ticket-BB-15927
@ticket-BB-16015
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attributes import with incorrect configuration
  In order to effectively manage attributes for Product entity
  As an Administrator
  I need to be able to import product attributes

  Scenario: Data Template for Product Attributes
    Given I login as administrator
    And go to Products/ Product Attributes
    When I download Product Attributes' Data Template file
    Then I see fieldName column
    And I see type column
    And I see entity.label column
    And I see entity.description column
    And I see entity.contact_information column
    And I see form.is_enabled column
    And I see extend.length column
    And I see importexport.header column
    And I see importexport.order column
    And I see importexport.identity column
    And I see importexport.excluded column
    And I see attachment.mimetypes column
    And I see attribute.searchable column
    And I see attribute.filterable column
    And I see attribute.filter_by column
    And I see attribute.sortable column
    And I see frontend.is_editable column
    And I see frontend.is_displayable column
    And I see email.available_in_template column
    And I see datagrid.is_visible column
    And I see datagrid.show_filter column
    And I see datagrid.order column
    And I see view.is_displayable column
    And I see view.priority column
    And I see search.searchable column
    And I see dataaudit.auditable column
    And I see extend.precision column
    And I see extend.scale column
    And I see attachment.maxsize column
    And I see attachment.width column
    And I see attachment.height column

  Scenario: Import Product Attributes as "Serialized fields"
    Given I fill template with data:
      | fieldName     | type     | entity.label  | entity.description | form.is_enabled | importexport.header | importexport.order | importexport.identity | importexport.excluded | attachment.mimetypes | attribute.searchable | attribute.filterable | attribute.filter_by | attribute.sortable | frontend.is_editable | frontend.is_displayable | email.available_in_template | datagrid.is_visible | datagrid.show_filter | datagrid.order | view.is_displayable | view.priority | search.searchable |
      | DateTimeField | datetime | DateTimeField | description_value  | yes             | header_value1       | 19                 | no                    | no                    |                      | no                   | no                   | exact_value         | yes                | yes                  | yes                     | yes                         | 0                   | no                   | 9              | yes                 | 7             | yes               |
      | TextField     | text     | TextField     | description_value  | yes             | header_value2       | 5                  | no                    | no                    |                      | yes                  | yes                  | exact_value         | no                 | yes                  | yes                     | yes                         | 0                   | no                   | 20             | yes                 | 10            | yes               |
    When I import file
    And Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"
    And I should see DateTimeField in grid with following data:
      | Storage Type | Serialized field |
    And I should see TextField in grid with following data:
      | Storage Type | Serialized field |

  Scenario: Update product family with new attributes
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [DateTimeField,TextField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | DateTimeField | <DateTime:today> |
      | TextField     | Test Value       |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I login as AmandaRCole@example.org buyer
    When I am on "/product"
    Then I should see "SKU123" product

  Scenario: Validation for attribute configuration like filterable/sortable/searchable
    Given I login as administrator
    And I go to Products / Product Attributes
    And I fill template with data:
      | fieldName       | type     | entity.label      | attribute.filterable | attribute.searchable | attribute.sortable |
      | validationtest1 | datetime | validation test 1 | yes                  | yes                  | yes                |
      | validationtest2 | text     | validation test 2 | yes                  | yes                  | yes                |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 3 processed: 0, read: 2, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. Attribute with datetime type cannot be filterable."
    And I should see "Error in row #1. Attribute with datetime type cannot be searchable."
    And I should see "Error in row #2. Attribute with text type cannot be sortable."
    And I login as administrator

  Scenario: Check that with right config validation will be passed without any errors
    When I go to Products / Product Attributes
    Then I should not see "Update schema"
    When I fill template with data:
      | fieldName       | type     | entity.label      | attribute.filterable | attribute.searchable | attribute.sortable |
      | validationtest1 | datetime | validation test 1 | no                   | no                   | yes                |
      | validationtest2 | text     | validation test 2 | yes                  | yes                  | no                 |
    When I try import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text

  Scenario: Validation for attribute configuration with reserved words
    Given I login as administrator
    And I go to Products / Product Attributes
    And I fill template with data:
      | fieldName | type      | entity.label |
      | image     | multiEnum | image        |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. fieldName: The 'image' word is reserved for system purposes."
