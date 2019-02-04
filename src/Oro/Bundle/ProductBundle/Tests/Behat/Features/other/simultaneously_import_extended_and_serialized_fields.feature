
@ticket-BB-14777
@regression
Feature: Simultaneously import extended and serialized fields
  In order to effectively manage extend fields for entities
  As an Administrator
  I want to have the possibility to import extended and serialized fields from one csv file simultaneously

  Scenario: Update schema button appears when importing text and boolean fields from csv
    Given I login as administrator
    And I go to Products/Product Attributes
    When I download "product" extend entity Data Template file
    Given I fill template with data:
      | fieldName          | type     | entity.label       | entity.description | entity.contact_information                    | form.is_enabled | extend.length | organization.applicable | importexport.header | importexport.order | importexport.identity | importexport.excluded | attachment.mimetypes | attribute.searchable | attribute.filterable | attribute.filter_by | attribute.sortable | attribute.enabled | attribute.visible | email.available_in_template | datagrid.is_visible | datagrid.show_filter | datagrid.order | view.is_displayable | view.priority | search.searchable | search.title_field | dataaudit.auditable |
      | field_datetime_pf1 | datetime | field_datetime_pf1 | description_value  |                                               | yes             |               |                         | header_value        | 19                 |                       | no                    |                      | yes                  | yes                  | exact_value         | yes                | yes               | yes               | yes                         | 1                   | yes                  | 9              | yes                 | 7             | yes               |                    | no                  |
      | field_text_pf1     | text     | field_text_pf1     | description_value  | oro.marketinglist.entity_config.choices.email | yes             |               |                         | header_value        | 5                  | no                    | no                    |                      | yes                  | yes                  | exact_value         | yes                | yes               | yes               | yes                         | 1                   | yes                  | 20             | yes                 | 10            | yes               |                    | no                  |

    When I import file
    And I reload the page
    Then I should see an "Update Schema" element
