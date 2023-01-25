@ticket-BB-21950
@fixture-OroPricingBundle:PricelistsForExport.yml

Feature: Export product price attribute data
  In order to export products prices attribute data
  As an Administrator
  I want to have the Export button on the Product page

  Scenario: Export product price attribute data filtered by category
    Given I login as administrator
    And I go to Products/ Products
    And I click "NewCategory"
    And I click on "ExportButtonDropdown"
    And I should see "Export Related Products"
    When I click "Export Price Attribute Data"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 4 price attribute product prices were exported. Download" text
