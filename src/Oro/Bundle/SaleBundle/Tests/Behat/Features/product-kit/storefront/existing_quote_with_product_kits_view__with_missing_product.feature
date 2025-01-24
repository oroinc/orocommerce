@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/storefront/existing_quote_with_product_kits__product.yml
@fixture-OroSaleBundle:product-kit/storefront/existing_quote_with_product_kits_view__with_missing_product__quote.yml

Feature: Existing Quote with Product Kits view - with Missing Product

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Remove a product
    Given I proceed as the Admin
    And I login as administrator
    When go to Products/ Products
    And click delete "simple-product-05" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message

  Scenario: View Quote
    Given I proceed as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Quotes"
    And I click view PO013 in grid
    Then I should see Quote Frontend Page with data:
      | PO Number | PO013 |
    And should see following "Frontend Quote Grid" grid:
      | Item                                                                                                                             | Quantity     | Unit Price |
      | Simple Product 01 SKU: simple-product-01 My Notes: Customer Notes 1 Seller Notes: Seller Notes 1                                 | 1 pc or more | $2.00      |

      | Product Kit 01 SKU: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 3 pieces Simple Product 05 - Deleted  | 1 pc or more | $104.69    |

      | Product Kit 01 SKU: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 3 pieces Simple Product 04 - Disabled | 1 pc or more | $100.00    |
    And I should see a "Simple Product 03 Link" element
    And I should not see a "Simple Product 05 - Deleted Link" element
    And I should not see a "Simple Product 04 - Disabled Link" element

  Scenario: Check Accept and Submit form
    When I click "Accept and Submit to Order"
    Then should see following "Quote View Grid" grid:
      | Product                                                                                                                          | Select An Offer      | Quantity To Order | Unit Price |
      | Simple Product 01 SKU #: simple-product-01                                                                                       | 1 pc or more $2.00   | pc                | $2.00      |
      # Quantity Offers
      | 1 pc or more                                                                                                                     | $2.00                |                   |            |
      | My Notes: Customer Notes 1                                                                                                       |                      |                   |            |
      | Seller Notes: Seller Notes 1                                                                                                     |                      |                   |            |

      | Product Kit 01 SKU #: product-kit-01 Optional Item2 pieces Simple Product 03 Mandatory Item3 pieces Simple Product 05 - Deleted  | 1 pc or more $104.69 | pc                | $104.69    |
      # Quantity Offers
      | 1 pc or more                                                                                                                     | $104.69              |                   |            |

      | Product Kit 01 SKU #: product-kit-01 Optional Item2 pieces Simple Product 03 Mandatory Item3 pieces Simple Product 04 - Disabled | 1 pc or more $100.00 | pc                | $100.00    |
      # Quantity Offers
      | 1 pc or more                                                                                                                     | $100.00              |                   |            |
    And "Storefront Quote Demand Form" must contains values:
      | Line Item 1 Quantity To Order | 1 |
      | Line Item 2 Quantity To Order | 1 |
    And I should see a "Simple Product 03 Link" element
    And I should not see a "Simple Product 05 - Deleted Link" element
    And I should not see a "Simple Product 04 - Disabled Link" element
