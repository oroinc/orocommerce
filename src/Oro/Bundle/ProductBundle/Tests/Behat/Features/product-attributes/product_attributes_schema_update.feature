@regression
@ticket-BB-13273
Feature: Product attributes schema update
  In order to effectively manage attributes for Product entity
  As an Administrator
  I need to be able to manage serialized fields without the need to update schema

  Scenario: Import BigInt Product Attribute as "Serialized field"
    Given I fill template with data:
      | fieldName        | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntSerialized | bigint | FieldText Label | no                   | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"
    When I check "BigInt" in "Data Type" filter
    Then I should see following grid:
      | Name             | Storage Type     |
      | bigIntSerialized | Serialized field |
