@ticket-BB-10272
@automatically-ticket-tagged
@fixture-OroPricingBundle:PricelistsForExport.yml

Feature: Export Products in Price list
  In order to export products from price list
  As an Administrator
  I want to have the Export button on the Price List detail page

  Scenario: Export Products in Price List
    Given I login as administrator
    And I go to Sales/ Price Lists
    And I click view "priceListForExport" in grid
    When I press "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 4 product prices were exported. Download" text
    And Exported file for "ProductPrice" contains following rows in any order:
      | Product SKU | Quantity | Unit Code | Price   | Currency |
      | PSKU1       | 1        | item      | 6.0000  | USD      |
      | PSKU2       | 2        | item      | 10.0000 | USD      |
      | PSKU3       | 5        | item      | 33.0000 | USD      |
      | PSKU4       | 1        | item      | 4.0000  | USD      |
    And I click Logout in user menu
