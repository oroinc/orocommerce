@regression
@feature-BB-19874
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml

Feature: Products grid frontend export with additional attributes
  In order to ensure frontend products grid export works correctly
  As a buyer
  I want to check product attributes could be configured to use in product export

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_product.product_data_export_enabled |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Create multi-select attribute
    Given I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | MultiSelectField |
      | Type       | Multi-Select     |
    And I click "Continue"
    And I fill form with:
      | Exportable | 1 |
    And I set Options with:
      | Label               |
      | TestMultiValueOne   |
      | TestMultiValueTwo   |
      | TestMultiValueThree |
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Enable featured product attribute for export
    Given I go to Products / Product Attributes
    And click edit "featured" in grid
    And I fill form with:
      | Exportable | 1 |
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default" in grid
    And I fill "Product Family Form" with:
      | Attributes | [MultiSelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update MultiSelectField in product
    Given I go to Products/ Products
    And I filter SKU as is equal to "PSKU5"
    When I click "Edit" on row "PSKU5" in grid
    And I check "TestMultiValueOne"
    And I check "TestMultiValueThree"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check export filtered products is working correctly
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Search Button"
    And I set range filter "Price" as min value "5" and max value "7" use "each" unit
    Then I should see "PSKU5"
    And I should see "PSKU7"
    And I should not see "PSKU4"
    And I should see an "Frontend Product Grid Export Button" element
    When I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id | featured | MultiSelectField.1.name | MultiSelectField.2.name |
      | Product 5 | PSKU5 | in_stock            | 0        | TestMultiValueOne       | TestMultiValueThree     |
      | Product 7 | PSKU7 | out_of_stock        | 0        |                         |                         |
