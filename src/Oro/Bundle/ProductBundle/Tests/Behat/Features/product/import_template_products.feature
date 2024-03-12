@feature-BB-21122
@ticket-BB-22959

Feature: Import Template Products
  In order to import products from template file
  As an Administrator
  I want to have the ability to download import template examples and import products from file

  Scenario: Verify administrator is able validate products from Product Template file
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    When I validate downloaded template file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3" text

  Scenario: Verify administrator is able import products from Product Template file
    Given I import downloaded template file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 3, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see following grid:
      | SKU     | Name                  | Status   | Inventory Status |
      | sku_003 | Product Kit           | Disabled | In Stock         |
      | sku_002 | Product Second Simple | Enabled  | In Stock         |
      | sku_001 | Product Simple        | Enabled  | In Stock         |

  Scenario: Check Product Simple
    Given I click view "sku_001" in grid
    Then I should see product with:
      | SKU              | sku_001                             |
      | Name             | Product Simple                      |
      | Type             | Simple                              |
      | Product Family   | Default                             |
      | Is Featured      | No                                  |
      | New Arrival      | No                                  |
      | Brand            | N/A                                 |
      | Unit             | item (fractional, 3 decimal digits) |
      | Tax Code         | N/A                                 |
    And I should see "Product Simple Description"
    And I should see "Product Simple Short Description"
    And I should see following product additional units:
      | kilogram | 0 | 5 | No |

  Scenario: Check Product Second Simple
    Given I go to Products/Products
    When I click view "sku_002" in grid
    Then I should see product with:
      | SKU              | sku_002                             |
      | Name             | Product Second Simple               |
      | Type             | Simple                              |
      | Product Family   | Default                             |
      | Is Featured      | No                                  |
      | New Arrival      | No                                  |
      | Brand            | N/A                                 |
      | Unit             | item (fractional, 3 decimal digits) |
      | Tax Code         | N/A                                 |
    And I should see "Product Second Simple Description"
    And I should see "Product Second Simple Short Description"
    And I should see following product additional units:
      | set | 0 | 5 | No |

  Scenario: Check Product Kit
    Given I go to Products/Products
    When I click view "sku_003" in grid
    Then I should see product with:
      | SKU              | sku_003                             |
      | Name             | Product Kit                         |
      | Type             | Kit                                 |
      | Product Family   | Default                             |
      | Is Featured      | No                                  |
      | New Arrival      | No                                  |
      | Brand            | N/A                                 |
      | Unit             | item (fractional, 3 decimal digits) |
      | Tax Code         | N/A                                 |
    And I should see "Product Kit Description"
    And I should see "Product Kit Short Description"
    And I should see following product additional units:
      | set | 0 | 5 | No |
    And I should see "Additional Unit Optional No Minimum Quantity N/A Maximum Quantity N/A Unit Of Quantity item (fractional, 3 decimal digits) Products SKU_001 - Product Simple" in the "Product Kit Item 1" element
    And I should see "Base Unit Optional Yes Minimum Quantity 1 Maximum Quantity 2 Unit Of Quantity item (fractional, 3 decimal digits) Products SKU_001 - Product Simple SKU_002 - Product Second Simple" in the "Product Kit Item 2" element
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 1
    When I click "Kit Item 2 Toggler"
    Then records in "Kit Item 2 Products Grid" should be 2
