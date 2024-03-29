@regression
@ticket-BB-20552
@fixture-OroLocaleBundle:GermanLocalization.yml

Feature: Slug generations based on imported product in different localizations
  In order to provide users with readable product URLs in different locations
  As administrator
  I need to be able to import products and get the generated Slug URL regardless of the product name locale

  Scenario: Import products with names in different localizations
    Given I login as administrator
    And go to Products/ Products
    And open "Products" import tab
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU  | Product Family.Code | Type   | Status  | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Name.default.value | Name.default.fallback | Name.English (United States).value | Name.English (United States).fallback | Name.German_Loc.value | Name.German_Loc.fallback |
      | SKU1 | default_family      | simple | enabled | in_stock            | set                        | 1                          | ORO PRODUCT        |                       |                                    | system                                | ORO PRODUCT DE        |                          |
    And import file
    And reload the page
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Check localized slugs
    Given I go to Products/Products
    When I click view "SKU1" in grid
    Then I should see text matching "/oro-product"
    And should see text matching "/oro-product-de"
