@regression
@feature-BB-19874
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml

Feature: Products grid frontend export with prices
  In order to ensure frontend products grid export with prices works correctly
  As a buyer
  I want to check prices can be configured for export and products can be exported with prices correctly

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_product.product_data_export_enabled |

  Scenario: Enable export product listing with Price attributes in admin console
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer Groups
    And I click Configuration AmandaRColeGroup in grid
    And I follow "Commerce/Product/Customer Settings" on configuration sidebar
    And uncheck "Use Website" for "Include Product Prices" field
    And I fill "System Config Form" with:
      | Include Product Prices | true |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check price attribute could be enabled in product export
    Given I go to Products/ Price Attributes
    And click edit "Price Attribute" in grid
    And I fill form with:
      | Enabled in Product Export | 1 |
    When I save and close form
    Then I should see "Price Attribute has been saved" flash message

  Scenario: Check export products with product price attribute
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Search Button"
    And I set range filter "Price" as min value "5" and max value "7" use "each" unit
    And I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id | price        | priceAttribute |
      | Product 5 | PSKU5 | in_stock            | $5.00 / each | $5.00 / each   |
      | Product 7 | PSKU7 | out_of_stock        | $7.00 / each | $7.00 / each   |

  Scenario: Enable in admin console Tier Prices in product export
    Given I proceed as the Admin
    And I go to Customers/ Customers
    And I click Configuration AmandaRCole in grid
    And I follow "Commerce/Product/Customer Settings" on configuration sidebar
    And uncheck "Use Customer Group" for "Include Price Tiers" field
    And I fill "System Config Form" with:
      | Include Price Tiers | true |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check export products with product price attributes and tier prices
    Given I proceed as the Buyer
    When I click "Search Button"
    And I set range filter "Price" as min value "5" and max value "7" use "each" unit
    And I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id | price        | priceAttribute | tier_prices     |
      | Product 5 | PSKU5 | in_stock            | $5.00 / each | $5.00 / each   | $5.00 \| 1 each |
      | Product 7 | PSKU7 | out_of_stock        | $7.00 / each | $7.00 / each   | $7.00 \| 1 each |
