@regression
@feature-BB-19874
@fixture-OroProductBundle:products_grid_frontend.yml

Feature: Products grid frontend export with additional attributes
  In order to ensure frontend products grid export works correctly
  As a buyer
  I want to check product attributes could be configured to use in product export

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I set configuration property "oro_product.product_data_export_enabled" to "1"

  Scenario: Enable product attributes for export
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Product Attributes
    And click edit "featured" in grid
    And I fill form with:
      | Can Be Exported | 1 |
    And I save and close form

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
    And email with Subject "Products export result is ready." containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id | featured |
      | Product 5 | PSKU5 | in_stock            | 0        |
      | Product 7 | PSKU7 | out_of_stock        | 0        |
