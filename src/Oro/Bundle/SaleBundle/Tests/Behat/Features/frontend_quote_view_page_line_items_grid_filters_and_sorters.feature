@regression
@fixture-OroSaleBundle:QuoteProductOfferFixture.yml

Feature: Frontend Quote view page line items grid filters and sorters

  Scenario: Check Grid
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Quotes"
    And I click "View" on row "PO1" in grid
    Then I should see "3 products"
    And I should see following grid:
      | Item                | Quantity        | Unit Price |
      | Product1 SKU: psku1 | 1 item or more  | $5.00      |
      | Product2 SKU: psku2 | 5 ea or more    | $15.00     |
      | Product3 SKU: psku3 | 10 sets or more | $25.00     |
    And I shouldn't see "Actions" column in grid

  Scenario: Sorting grid by Quantity
    When sort grid by Quantity
    Then I should see that Quantity in 1 row is equal to "1 item or more"
    But when I sort grid by Quantity again
    Then I should see that Quantity in 1 row is equal to "10 sets or more"

  Scenario: Sorting grid by Unit Price
    When sort grid by Unit Price
    Then I should see that Unit Price in 1 row is equal to "$5.00"
    But when I sort grid by Unit Price again
    Then I should see that Unit Price in 1 row is equal to "$25.00"

  Scenario: Check SKU filter
    When I set filter SKU as is equal to "psku2"
    Then I should see following grid:
      | Item                | Quantity     | Unit Price |
      | Product2 SKU: psku2 | 5 ea or more | $15.00     |
    And I reset "SKU" filter in "Frontend Quote Grid Filters" sidebar

  Scenario: Check Quantity filter
    When I set filter Quantity as not equals "1"
    Then I should see following grid:
      | Item                | Quantity        | Unit Price |
      | Product3 SKU: psku3 | 10 sets or more | $25.00     |
      | Product2 SKU: psku2 | 5 ea or more    | $15.00     |
    And I reset "Quantity" filter in "Frontend Quote Grid Filters" sidebar

  Scenario: Check Product Unit filter
    When I check "each" in Product Unit filter
    Then I should see following grid:
      | Item                | Quantity     | Unit Price |
      | Product2 SKU: psku2 | 5 ea or more | $15.00     |
    And I click "Clear All Filters"

  Scenario: Check Unit Price filter
    When I set filter Unit Price as not equals "15"
    Then I should see following grid:
      | Item                | Quantity        | Unit Price |
      | Product3 SKU: psku3 | 10 sets or more | $25.00     |
      | Product1 SKU: psku1 | 1 item or more  | $5.00      |
    And I reset "Unit Price" filter in "Frontend Quote Grid Filters" sidebar
