@regression
@ticket-BB-19248
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attributes import triggers product reindex

  Scenario: Create different window session
    Given sessions active:
      | admin    |first_session |
      | customer |second_session|

  Scenario: Import Product Attributes
    Given I proceed as the admin
    And login as administrator
    And I go to Products / Product Attributes
    When I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName | type | entity.label | entity.description | form.is_enabled | importexport.header |  attachment.mimetypes | attribute.searchable | attribute.filterable | attribute.filter_by | attribute.sortable | attribute.enabled | attribute.visible | email.available_in_template | datagrid.is_visible | datagrid.show_filter | datagrid.order | view.is_displayable | view.priority | search.searchable |enum.enum_options.0.label | enum.enum_options.0.is_default|
      | Country   | enum | Country      | description_value  | yes             | header_value1       |                       | no                   | no                   | exact_value         | no                 | yes               | yes               | yes                         | 0                   | no                   | 9              | yes                 | 7             | no                |USA                       | no                            |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see "Update schema"
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Country] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | Country | USA |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check attribute properties on the storefront
    Given I proceed as the customer
    And I login as AmandaRCole@example.org buyer
    When I type "USA" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I click "Grid Filters Button"
    Then I should not see "Filter By Country" in the "ProductFrontendGridFiltersBlock" element
    Then I should not see "Country" in the "Frontend Product Grid Sorter" element

  Scenario: Update attribute properties via import
    Given I proceed as the admin
    And login as administrator
    And I go to Products / Product Attributes
    When I fill template with data:
      | fieldName | type | entity.label | entity.description | form.is_enabled | importexport.header |  attachment.mimetypes | attribute.searchable | attribute.filterable | attribute.filter_by | attribute.sortable | attribute.enabled | attribute.visible | email.available_in_template | datagrid.is_visible | datagrid.show_filter | datagrid.order | view.is_displayable | view.priority | search.searchable |enum.enum_options.0.label | enum.enum_options.0.is_default|
      | Country   | enum | Country      | description_value  | yes             | header_value1       |                       | yes                   | yes                  | exact_value         | yes                | yes               | yes               | yes                         | 0                   | no                   | 9              | yes                 | 7             | yes               |USA                       | no                            |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"

  Scenario: Check updated attribute properties on the storefront
    Given I proceed as the customer
    And I login as AmandaRCole@example.org buyer
    When I type "USA" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I click "Grid Filters Button"
    And I check "USA" in Country filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "Country" options
