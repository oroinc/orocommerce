@ticket-BB-10299
@fixture-OroProductBundle:ProductsExportFixture.yml
Feature: Export Products
  In order to export products
  As an Administrator
  I want to have an ability Export all products from the system into the file

  Scenario: Verify administrator is able Export Products from the system
    Given I login as administrator
    And I go to Products/ Products
    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    #todo: to be implemented in scope of CRM-7599.
    #And Email should contains the following "Export performed successfully. 5 products were exported. Download" text
    And Exported file with Products contains at least the following data:
      | sku   | names.default.value | status   |
      | PSKU1 | Product 1           | enabled  |
      | PSKU2 | Product 2           | enabled  |
      | PSKU3 | Product 3           | enabled  |
      | PSKU4 | Product 4           | enabled  |
      | PSKU5 | Product5(disabled)  | disabled |
