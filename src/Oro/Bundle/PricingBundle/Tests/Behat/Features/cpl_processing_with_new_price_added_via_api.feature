@regression
@behat-test-env
@pricing-storage-combined
@ticket-BB-26237
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL processing with new price added via API

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | API   | second_session |

  Scenario: Prepare API sandbox session
    Given I enable API
    And I proceed as the API
    And I login as administrator

  Scenario: Prepare Admin session
    Given I proceed as the Admin
    And I login as administrator

  Scenario Outline: Remember product IDs
    When I go to Products/ Products
    And I click view <SKU> in grid
    And I remember ID from current URL as "<VAR>"

    Examples:
      | SKU   | VAR         |
      | PSKU2 | PRODUCT2.id |

  Scenario: Create Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL        |
      | Currencies | US Dollar ($) |
      | Active     | true          |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And I remember ID from current URL as "TESTPL.id"

  Scenario: Assign Price List to customer
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Add 2 Prices with Batch API to clean PL
    Given I proceed as the API
    When I go to "/admin/api/doc"
    And I click on "API List Operations for productprices"
    And I click on "API Create or update a list of Product Prices"
    And I click on "API Active Section Sandbox"
    And I type '{"data":[{"type":"productprices","attributes":{"quantity":1,"value":"102.00","currency":"USD"},"relationships":{"priceList":{"data":{"type":"pricelists","id":"$TESTPL.id$"}},"product":{"data":{"type":"products","id":"$PRODUCT2.id$"}},"unit":{"data":{"type":"productunits","id":"item"}}}}]}' in "API Content"
    And I click on "API Try"

  Scenario: Check prices added via Batch API to not empty price list are added as expected
    Given I proceed as the Admin
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value  | Currency |
      | PSKU2       | Product 2    | 1        | item | 102.00 | USD      |

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price   |
      | PSKU2 | $102.00 |
