@regression
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/create_quote_from_rfq_with_product_kits__rfq.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml

Feature: Frontend RFQ view page line items grid filter

  Scenario: Check RFQ view page line items grid filter
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click view PO013 in grid
    And I should see "2 products"
    Then I shouldn't see "Actions" column in grid
    When I set filter SKU as contains "simple"
    Then I should see following grid:
      | Item                                     | Requested Quantity | Target Price |
      | Simple Product 01 SKU: simple-product-01 | 1 pc               | $2.00        |

    When I set filter SKU as is equal to "product-kit-01"
    Then I should see following grid:
      | Item                                                                                                                  | Requested Quantity | Target Price |
      | Product Kit 01 SKU: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 3 pieces Simple Product 01 | 1 pc               | $104.69      |
