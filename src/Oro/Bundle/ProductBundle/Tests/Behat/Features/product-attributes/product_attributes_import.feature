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
      | bigIntTable | bigint | FieldText Label | yes                  | 0                   |
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
    And I reset Data Type filter

  Scenario: Import BigInt Product Attribute as "Table column" when field which requires schema update is changed
    Given I fill template with data:
      | fieldName   | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntTable | bigint | new Label       | yes                  | 1                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    And I reload the page
    Then I should see "Update schema"
    And I click update schema
    And I should see Schema updated flash message

  Scenario: It should be impossible to import attributes with similar or invalid names or properties
    Given I fill template with data:
      | fieldName            | type   | entity.label  | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name   | bigint | label value 1 | no                   | 0                   |
      | correctFieldName     | bigint | label value 2 | no                   | 0                   |
      | inc@rrect_field_name | bigint | label value 3 | no                   | 0                   |
      | incorrect_field      | qwerty | label value 4 | no                   | 0                   |
      | UNION                | bigint | label value 5 | no                   | 0                   |
      |                      | bigint | label value 6 | no                   | 0                   |
      | correct_field_name_2 |        | label value 7 | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 6 processed: 1, read: 7, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "label value 1" in grid
    And I should not see "correctFieldName"
    And I should not see "inc@rrect_field_name"
    And I should not see "incorrect_field"
    And I should not see "UNION"
    And I should not see "correct_field_name_2"
    And I should not see "Update schema"

  Scenario: It should be impossible to import attributes with correct names
    When I fill template with data:
      | fieldName            | type   | entity.label  | datagrid.show_filter | datagrid.is_visible |
      | Tv                   | string | label value 2 | no                   | 0                   |
      | Text_underscore_text | string | label value 3 | no                   | 0                   |
      | Myand4               | string | label value 4 | no                   | 0                   |
      | koko                 | string | label value 5 | no                   | 0                   |
      | LOREM                | string | label value 6 | no                   | 0                   |
      | SunSet               | string | label value 7 | no                   | 0                   |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 6, read: 6, added: 6, updated: 0, replaced: 0" text
    When I reload the page
    And I should see Tv in grid
    And I should see "label value 2" in grid
    And I should see Text_underscore_text in grid
    And I should see "label value 3" in grid
    And I should see Myand4 in grid
    And I should see "label value 4" in grid
    And I should see koko in grid
    And I should see "label value 5" in grid
    And I should see LOREM in grid
    And I should see "label value 6" in grid
    And I should see SunSet in grid
    And I should see "label value 7" in grid
    And I should not see "Update schema"

  Scenario: It should be impossible to import attributes with incorrect names
    When I fill template with data:
      | fieldName                                          | type   | entity.label   | datagrid.show_filter | datagrid.is_visible |
      | null                                               | string | label value 8  | no                   | 0                   |
      | LoremIpsumLoremIpsumLoremIpsumLoremIpsumLoremIpsum | string | label value 9  | no                   | 0                   |
      | лорем_иъий                                         | string | label value 10 | no                   | 0                   |
      | A                                                  | string | label value 11 | no                   | 0                   |
      | U+004C                                             | string | label value 12 | no                   | 0                   |
      | &^$                                                | string | label value 13 | no                   | 0                   |
      | 4&a                                                | string | label value 14 | no                   | 0                   |
      | &A                                                 | string | label value 15 | no                   | 0                   |
      | #^*()                                              | string | label value 16 | no                   | 0                   |
      | SKU                                                | string | label value 17 | no                   | 0                   |
      | _loremipsum                                        | string | label value 18 | no                   | 0                   |
    And I import file
    Then Email should contains the following "Errors: 11 processed: 0, read: 11, added: 0, updated: 0, replaced: 0" text
    When I reload the page
    Then there are 22 records in grid
    And I should see following grid:
      | NAME                         | DATA TYPE       | LABEL             | TYPE   | SCHEMA STATUS | STORAGE TYPE     | ORGANIZATION | VISIBLE | AUDITABLE | PRODUCT FAMILIES |
      | LOREM                        | String          | label value 6     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | Myand4                       | String          | label value 4     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | SunSet                       | String          | label value 7     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | Text_underscore_text         | String          | label value 3     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | Tv                           | String          | label value 2     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | bigIntSerialized             | BigInt          | FieldText Label   | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | bigIntTable                  | BigInt          | new Label         | Custom | Active        | Table column     | All          | Yes     | No        |                  |
      | brand                        | System relation | Brand             | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
      | correct_field_name           | BigInt          | label value 1     | Custom | Active        | Serialized field | All          | Yes     | No        |                  |
      | descriptions                 | System relation | Description       | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
      | featured                     | Boolean         | Is Featured       | System | Active        | Table column     | All          | No      | No        | Default          |
      | images                       | System relation | Images            | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
      | inventory_status             | Select          | Inventory Status  | System | Active        | Table column     | All          | No      | Yes       | Default          |
      | koko                         | String          | label value 5     | Custom | Active        | Serialized field | All          | Yes      | No        |                  |
      | metaDescriptions             | Many to many    | Meta description  | System | Active        | Table column     | All          | No      | No        | Default          |
      | metaKeywords                 | Many to many    | Meta keywords     | System | Active        | Table column     | All          | No      | No        | Default          |
      | metaTitles                   | Many to many    | Meta title        | System | Active        | Table column     | All          | No      | No        | Default          |
      | names                        | System relation | Name              | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
      | newArrival                   | Boolean         | New Arrival       | System | Active        | Table column     | All          | No      | No        | Default          |
      | productPriceAttributesPrices | System relation | Product prices    | System | Active        | Table column     | All          | Yes     | No        | Default          |
      | shortDescriptions            | System relation | Short Description | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
      | sku                          | String          | SKU               | System | Active        | Table column     | All          | Yes     | Yes       | Default          |
    And I should not see "Update schema"

  Scenario: It should be impossible to updated columns with similar names
    Given I fill template with data:
      | fieldName          | type   | entity.label            | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name | bigint | FieldText Label         | no                   | 0                   |
      | correctFieldName   | bigint | FieldText Label updated | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "FieldText Label" in grid
    And I should not see "correctFieldName"
    And I should not see "FieldText Label updated"
    And I should not see "Update schema"

  Scenario: It should be possible to updated columns with the same name
    Given I fill template with data:
      | fieldName          | type   | entity.label            | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name | bigint | FieldText Label updated | no                   | 0                   |
      | correctFieldName   | bigint | FieldText Label         | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "FieldText Label updated" in grid
    And I should not see "correctFieldName"
    And I should not see "Update schema"

  Scenario: It should be possible to update columns with the same name from Entity Management
    Given I go to System/Entities/Entity Management
    And I filter Name as is equal to "Product"
    And I click View Product in grid
    And I fill template with data:
      | fieldName          | type   | entity.label                   | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name | bigint | FieldText Label second updated | no                   | 0                   |
      | correctFieldName   | bigint | FieldText Label                | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "FieldText Label second updated" in grid
    And I should not see "correctFieldName"
    And I should not see "Update schema"

  Scenario: It should be impossible to import columns with invalid field name
    Given I go to Products/ Product Attributes
    And I fill template with data:
      | fieldName                 | type   | entity.label       | datagrid.show_filter | datagrid.is_visible |
      | <script>alert(1)</script> | string | string field Label | no                   | 0                   |
    When I try import file
    Then I should not see "Import File Field Validation" element with text "The mime type of the file is invalid" inside "Import File Form" element
    When I reload the page
    Then I should not see "Update schema"
