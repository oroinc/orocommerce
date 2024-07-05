@ticket-BB-24009
@fixture-OroPricingBundle:PriceListForExportWithoutCalculationRules.yml

Feature: Export Products Prices from Price List without Calculation Rules
  In order to export products prices
  As an Administrator
  I want to have possibility to export Product Prices from Price List without Calculation Rules on detail page

  Scenario: Export Products Prices from Price List
    Given I login as administrator
    And I go to Sales/ Price Lists
    And I click view "PriceListForExportWithoutCalculationRules" in grid
    When I click "Export Button"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 1 product prices were exported. Download" text
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU1       |          | item      |       | USD      |
