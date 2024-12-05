@feature-BB-23538
@ticket-BB-23545
@ticket-BB-23546
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:product-kit/product_kit_with_taxes_and_promotion.yml

Feature: Create Order from Checkout with product kits and taxes
  As a buyer on the storefront
  I should see the correctly calculated taxes and discounts for orders with product kits

  Scenario: Feature Background
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I enable configuration options:
      | oro_tax.tax_enable |
    And I change configuration options:
      | oro_tax.use_as_base_by_default | destination |

  Scenario: Add a product kit line item
    Given I am on the homepage
    And I type "product-kit-01" in "search"
    And I click "Search Button"
    When I click "View Details" for "Product Kit 01" product
    Then I should see an "Configure and Add to Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $124.69 |
    When I click "Add to Shopping List" in modal window
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message
    When I follow "Shopping List" link within flash message "Product kit has been added to \"Shopping list\""
    Then I should see "Subtotal $124.69"
    And I should see "Discount -$12.47"
    And I should see "Total: $112.22"
    When I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Subtotal $124.69"
    And I should see "Discount -$12.47"
    And I should see "Shipping $3.00"
    And I should see "Tax $12.35"
    And I should see "Total: $127.57"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 1   | $124.6867 | $124.69 -$12.46867 $112.22 |
      | simple-product-01 | Mandatory Item: Simple Product 01 | 1   | $1.2345   |                            |

  Scenario: Change product kit line item quantity
    When I click "Edit items"
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "2" in "Shopping List Line Item 1 Quantity Input"
    And I click "Shopping List Line Item 1 Save Changes Button"
    Then I should see "Subtotal $249.37"
    And I should see "Discount -$24.94"
    And I should see "Total: $224.43"
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Subtotal $249.37"
    And I should see "Discount -$24.94"
    And I should see "Shipping $3.00"
    And I should see "Tax $24.69"
    And I should see "Total: $252.12"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 2   | $124.6867 | $249.37 -$24.93734 $224.43 |
      | simple-product-01 | Mandatory Item: Simple Product 01 | 1   | $1.2345   |                            |

  Scenario: Add product kit item line item product
    When I click "Edit items"
    And I click "Configure" on row "Product Kit 01" in grid
    And I click "Kit Item Line Item 2 Product 1"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $256.77 |
    When I click "Update Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see "Subtotal $256.77"
    And I should see "Discount -$25.68"
    And I should see "Total: $231.09"
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Subtotal $256.77"
    And I should see "Discount -$25.68"
    And I should see "Shipping $3.00"
    And I should see "Tax $25.43"
    And I should see "Total: $259.52"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 2   | $128.3867 | $256.77 -$25.67734 $231.09 |
      | simple-product-03 | Optional Item: Simple Product 03  | 1   | $3.7035   |                            |
      | simple-product-01 | Mandatory Item: Simple Product 01 | 1   | $1.2345   |                            |

  Scenario: Change product kit item line item product
    When I click "Edit items"
    And I click "Configure" on row "Product Kit 01" in grid
    And I click "Kit Item Line Item 2 Product 2"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $259.25 |
    When I click "Update Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see "Subtotal $259.25"
    And I should see "Discount -$25.93"
    And I should see "Total: $233.32"
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Subtotal $259.25"
    And I should see "Discount -$25.93"
    And I should see "Shipping $3.00"
    And I should see "Tax $25.92"
    And I should see "Total: $262.24"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 2   | $129.6267 | $259.25 -$25.92534 $233.32 |
      | simple-product-03 | Optional Item: Simple Product 03  | 1   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 1   | $2.469    |                            |

  Scenario: Change product kit item line item quantity
    When I click "Edit items"
    And I click "Configure" on row "Product Kit 01" in grid
    When I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 2 |
      | Kit Item Line Item 2 Quantity | 3 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $276.55 |
    When I click "Update Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see "Subtotal $276.55"
    And I should see "Discount -$27.66"
    And I should see "Total: $248.89"
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Subtotal $276.55"
    And I should see "Discount -$27.66"
    And I should see "Shipping $3.00"
    And I should see "Tax $27.65"
    And I should see "Total: $279.54"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 2   | $138.2767 | $276.55 -$27.65534 $248.89 |
      | simple-product-03 | Optional Item: Simple Product 03  | 2   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 3   | $2.469    |                            |

  Scenario: Add one more product kit line item
    When I click "Edit items"
    And I click "Configure" on row "Product Kit 01" in grid
    When I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 3 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $414.83 |
    When I click "Update Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Total: $373.35"
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Shipping $3.00"
    And I should see "Tax $41.48"
    And I should see "Total: $417.83"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 3   | $138.2767 | $414.83 -$41.48301 $373.35 |
      | simple-product-03 | Optional Item: Simple Product 03  | 2   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 3   | $2.469    |                            |

  Scenario: Product prices already include tax
    When I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And I reload the page
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Shipping $3.00"
    And I should see "Tax $37.71"
    And I should see "Total: $376.35"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 3   | $138.2767 | $414.83 -$41.48301 $373.35 |
      | simple-product-03 | Optional Item: Simple Product 03  | 2   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 3   | $2.469    |                            |

  Scenario: Calculate taxes after promotions
    When I disable configuration options:
      | oro_tax.product_prices_include_tax |
    And I enable configuration options:
      | oro_tax.calculate_taxes_after_promotions |
    And I reload the page
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Shipping $3.00"
    And I should see "Tax $37.33"
    And I should see "Total: $413.68"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 3   | $138.2767 | $414.83 -$41.48301 $373.35 |
      | simple-product-03 | Optional Item: Simple Product 03  | 2   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 3   | $2.469    |                            |

  Scenario: Product prices already include tax with enabled option calculate taxes after promotions
    When I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And I reload the page
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Shipping $3.00"
    And I should see "Tax $33.94"
    And I should see "Total: $376.35"
    And I should see following grid:
      | SKU               | Product                           | Qty | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 01                    | 3   | $138.2767 | $414.83 -$41.48301 $373.35 |
      | simple-product-03 | Optional Item: Simple Product 03  | 2   | $3.7035   |                            |
      | simple-product-02 | Mandatory Item: Simple Product 02 | 3   | $2.469    |                            |

  Scenario: Save order and check taxes
    When I disable configuration options:
      | oro_tax.product_prices_include_tax |
      | oro_tax.calculate_taxes_after_promotions |
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    When I follow "click here to review"
    Then I should see "Subtotal $414.83"
    And I should see "Discount -$41.48"
    And I should see "Shipping $3.00"
    And I should see "Tax $41.48"
    And I should see "Total $417.83"
    And I should see following "Order Line Items Grid" grid:
      | Product                                                                                                                                 | Quantity | Price     |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces $3.7035 Simple Product 03 Mandatory Item 3 pieces $2.469 Simple Product 02 | 3 pieces | $138.2767 |
