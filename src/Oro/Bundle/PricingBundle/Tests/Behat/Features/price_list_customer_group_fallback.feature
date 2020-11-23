@regression
@pricing-storage-combined
@ticket-BB-16301
@fixture-OroPricingBundle:ProductPricesForFallback.yml

Feature: Price List Customer Group fallback
  In order to have the ability to manage price list chains
  As an Administrator
  I want to be able to change the price list chain, price list fallback and customer group for customer and get expected prices at store frontend

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Configure price lists
    Given I proceed as the Admin
    And I go to System/Configuration
    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And I choose Price List "priceListForConfig" in 1 row
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to System/Websites
    And click Edit Default in grid
    And I choose Price List "priceListForWebsite" in 1 row
    And I submit form
    Then I should see "Website has been saved" flash message

  Scenario Outline: Create customer groups
    When I go to Customers / Customer Groups
    And click "Create Customer Group"
    And fill "Customer Group Form" with:
      | Name                | <Customer Group Name> |
      | Price List Fallback | <Price List Fallback> |
      | Price List          | <Price List>          |
    And save and close form
    Then should see "Customer group has been saved" flash message

    Examples:
      | Customer Group Name | Price List                 | Price List Fallback         |
      | CustomerGroup1      | priceListForCustomerGroup1 | Website                     |
      | CustomerGroup2      | priceListForCustomerGroup2 | Current customer group only |

  Scenario Outline: Check prices for new customer
    Given I proceed as the Admin

    When I go to Customers / Customers
    And click "Create Customer"
    And fill "Customer Form" with:
      | Name                | <Customer Name>       |
      | Group               | <Customer Group>      |
      | Price List Fallback | <Price List Fallback> |
      | Price List          | <Price List>          |
    And save and close form
    Then should see "Customer has been saved" flash message

    When I go to Customers / Customer Users
    And click "Create Customer User"
    And fill form with:
      | Password           | <CustomerUserEmail> |
      | Confirm Password   | <CustomerUserEmail> |
      | Customer           | <Customer Name>     |
      | First Name         | <Customer Name> FN  |
      | Last Name          | <Customer Name> LN  |
      | Email Address      | <CustomerUserEmail> |
      | Buyer (Predefined) | true                |
    And save and close form
    Then should see "Customer User has been saved" flash message

    When I proceed as the Buyer
    And I signed in as <CustomerUserEmail> on the store frontend
    And I should see "No Shopping Lists"
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product 1"
    And I should see "Your Price: <Expected Price> / item" for "PSKU1" product
    When I click "Add to Shopping List" for "PSKU1" product
    And I open shopping list widget
    And I should see "1 Item | <Expected Price>" in the "Shopping List Widget" element
    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal <Expected Price>"
    And I should see "Total <Expected Price>"

    Examples:
      | Customer Name | Customer Group | Price List            | Price List Fallback   | Expected Price | CustomerUserEmail     |
      | Customer1     | CustomerGroup1 |                       | Customer group        | $11.00         | Customer1@example.org |
      | Customer2     | CustomerGroup1 | priceListForCustomer1 | Customer group        | $1.00          | Customer2@example.org |
      | Customer3     |                | priceListForCustomer1 | Customer group        | $1.00          | Customer3@example.org |
      | Customer4     | CustomerGroup1 | priceListForCustomer1 | Current customer only | $1.00          | Customer4@example.org |
      | Customer5     |                | priceListForCustomer1 | Current customer only | $1.00          | Customer5@example.org |

  Scenario Outline: Create customer with fallback to Current customer only and without price lists assigned
    Given I proceed as the Admin

    When I go to Customers / Customers
    And click "Create Customer"
    And fill "Customer Form" with:
      | Name                | <Customer Name>       |
      | Group               | <Customer Group>      |
      | Price List Fallback | Current customer only |
    And save and close form
    Then should see "Customer has been saved" flash message

    When I go to Customers / Customer Users
    And click "Create Customer User"
    And fill form with:
      | Password           | <CustomerUserEmail> |
      | Confirm Password   | <CustomerUserEmail> |
      | Customer           | <Customer Name>     |
      | First Name         | <Customer Name> FN  |
      | Last Name          | <Customer Name> LN  |
      | Email Address      | <CustomerUserEmail> |
      | Buyer (Predefined) | true                |
    And save and close form
    Then should see "Customer User has been saved" flash message

    When I proceed as the Buyer
    And I signed in as <CustomerUserEmail> on the store frontend
    And I should see "No Shopping Lists"
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product 1"
    And I should see "Price not available" for "PSKU1" product

    Examples:
      | Customer Name | Customer Group | CustomerUserEmail     |
      | Customer6     | CustomerGroup1 | Customer6@example.org |
      | Customer7     |                | Customer7@example.org |

  Scenario Outline: Customer edits trigger combined price list change and subtotals invalidation
    Given I proceed as the Admin

    When I go to Customers / Customers
    And I click edit "<Customer Name>" in grid
    And fill "Customer Form" with:
      | Group               | <Customer Group>      |
      | Price List Fallback | <Price List Fallback> |
      | Price List          | <Price List>          |
    And save and close form
    Then should see "Customer has been saved" flash message

    When I proceed as the Buyer
    And I signed in as <CustomerUserEmail> on the store frontend
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product 1"
    And I should see "Your Price: <Expected Price> / item" for "PSKU1" product
    And I open shopping list widget
    And I should see "1 Item | <Expected Price>" in the "Shopping List Widget" element
    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal <Expected Price>"
    And I should see "Total <Expected Price>"

    Examples:
      | Customer Name | Customer Group | Price List            | Price List Fallback   | Expected Price | CustomerUserEmail     |
      | Customer1     | CustomerGroup1 | priceListForCustomer2 | Customer group        | $2.00          | Customer1@example.org |
      | Customer1     | CustomerGroup1 |                       | Customer group        | $11.00         | Customer1@example.org |
      | Customer1     |                |                       | Customer group        | $100.00        | Customer1@example.org |
      | Customer2     | CustomerGroup1 | priceListForCustomer2 | Customer group        | $2.00          | Customer2@example.org |
      | Customer2     | CustomerGroup2 | priceListForCustomer1 | Customer group        | $1.00          | Customer2@example.org |
      | Customer3     |                | priceListForCustomer2 | Customer group        | $2.00          | Customer3@example.org |
      | Customer3     | CustomerGroup1 | priceListForCustomer1 | Customer group        | $1.00          | Customer3@example.org |
      | Customer4     | CustomerGroup1 | priceListForCustomer2 | Current customer only | $2.00          | Customer4@example.org |
      | Customer4     | CustomerGroup2 | priceListForCustomer1 | Current customer only | $1.00          | Customer4@example.org |
      | Customer5     |                | priceListForCustomer2 | Current customer only | $2.00          | Customer5@example.org |
      | Customer5     | CustomerGroup1 | priceListForCustomer1 | Current customer only | $1.00          | Customer5@example.org |
