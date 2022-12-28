@regression
@feature-BB-19874
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsGridFrontendLocalized.yml

Feature: Products grid frontend export localized
  In order to ensure frontend products grid export works correctly in another localization
  As a buyer
  I want to export product listing in non-default localization

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations
    And I enable configuration options:
      | oro_product.product_data_export_enabled |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Update email notification template for Zulu
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "frontend_export_result_success"
    And I click "edit" on first row in grid
    And I click "Zulu"
    And fill "Email Template Form" with:
      | Subject Fallback | false                                |
      | Subject          | Zulu Products export result is ready |
    And I submit form
    Then I should see "Template saved" flash message

  Scenario: Check product data exports correctly
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "Zulu" localization
    And I type "SKUZULU1" in "search"
    And I click "Search Button"
    When I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Zulu Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | name           | sku      | inventory_status.id |
      | ProductInZulu1 | SKUZULU1 | in_stock            |
