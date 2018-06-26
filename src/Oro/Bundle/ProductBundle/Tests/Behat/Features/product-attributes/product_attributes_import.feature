@regression
@ticket-BB-13273
@ticket-BB-14555
Feature: Product attributes import
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
    And I see attribute.enabled column
    And I see attribute.visible column
    And I see email.available_in_template column
    And I see datagrid.is_visible column
    And I see datagrid.show_filter column
    And I see datagrid.order column
    And I see view.is_displayable column
    And I see view.priority column
    And I see search.searchable column
    And I see search.title_field column
    And I see dataaudit.auditable column
    And I see extend.precision column
    And I see extend.scale column
    And I see attachment.maxsize column
    And I see attachment.width column
    And I see attachment.height column
    And I see enum.enum_options.0.label column
    And I see enum.enum_options.0.is_default column
    And I see enum.enum_options.1.label column
    And I see enum.enum_options.1.is_default column
    And I see enum.enum_options.2.label column
    And I see enum.enum_options.2.is_default column

  Scenario: Import BigInt Product Attribute as "Serialized field"
    Given I fill template with data:
      | fieldName        | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntSerialized | bigint | FieldText Label | no                   | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"
    And I should see bigIntSerialized in grid with following data:
      | Storage Type | Serialized field |

  Scenario: Import BigInt Product Attribute as "Serialized field" when field which requires schema update is changed
    Given I fill template with data:
      | fieldName        | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntSerialized | bigint | FieldText Label | no                   | 1                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"

  Scenario: Import BigInt Product Attribute as "Table column"
    Given I fill template with data:
      | fieldName   | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntTable | bigint | FieldText Label | 1                    | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    And I reload the page
    Then I should see "Update schema"
    When I check "BigInt" in "Data Type" filter
    Then I should see following grid:
      | Name             | Storage Type     |
      | bigIntSerialized | Serialized field |
      | bigIntTable      | Table column     |
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: It should be impossible to import attributes with similar or invalid names or properties
    When I fill template with data:
      | fieldName            | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name   | bigint | FieldText Label | 1                    | 0                   |
      | correctFieldName     | bigint | FieldText Label | 1                    | 0                   |
      | inc@rrect_field_name | bigint | FieldText Label | 1                    | 0                   |
      | incorrect_field      | qwerty | FieldText Label | 1                    | 0                   |
      | UNION                | bigint | FieldText Label | 1                    | 0                   |
      |                      | bigint | FieldText Label | 1                    | 0                   |
      | correct_field_name_2 |        | FieldText Label | 1                    | 0                   |
    And I import file
    Then Email should contains the following "Errors: 6 processed: 1, read: 7, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should not see "correctFieldName"
    And I should not see "inc@rrect_field_name"
    And I should not see "incorrect_field"
    And I should not see "UNION"
    And I should not see "correct_field_name_2"
    And I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message
