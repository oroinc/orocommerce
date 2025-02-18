@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/create_new_quote_from_existing_quote_with_product_kits_validation__with_missing_product__quote.yml

Feature: Create New Quote from existing Quote with Product Kits Validation - with Missing Product

  Scenario: Remove a product
    Given I login as administrator
    When go to Products/ Products
    And click delete "simple-product-05" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message

  Scenario: Check the kit item line items with a missing and disabled products on the Quote view page
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status  | PO Number |
      | Amanda Cole   | Sent to Customer | PO013     |
    And click view "PO013" in grid
    Then I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                            | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                                  | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 05 - Deleted  | 1 pc or more | $104.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 04 - Disabled | 1 pc or more | $100.00 |

  Scenario: Create new Quote, Redirect to new Quote, Internal status: Draft
    When I click "Create new Quote"
    And click "Submit"
    Then I should see "Quote #2 successfully created" flash message
    And email with Subject "Quote #2 has been created" containing the following was sent:
      | Subject | Quote #2 has been created |
    And should see Quote with:
      | Quote #         | 2     |
      | PO Number       | PO013 |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                            | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                                  | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 05 - Deleted  | 1 pc or more | $104.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 04 - Disabled | 1 pc or more | $100.00 |

  Scenario: Check the Quote edit page
    When I click "Edit"
    Then "Quote Form" must contains values:
      | PO Number                   | PO013                                            |
      | Line Item 2 Product         | product-kit-01 - Product Kit 01                  |
      | Line Item 2 Quantity        | 1                                                |
      | Line Item 2 Unit            | piece                                            |
      | Line Item 2 Price           | 104.69                                           |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03            |
      | Line Item 2 Item 1 Quantity | 2                                                |
      | Line Item 2 Item 2 Product  | simple-product-05 - Simple Product 05 - Deleted  |
      | Line Item 2 Item 2 Quantity | 3                                                |
      | Line Item 3 Product         | product-kit-01 - Product Kit 01                  |
      | Line Item 3 Quantity        | 1                                                |
      | Line Item 3 Unit            | piece                                            |
      | Line Item 3 Price           | 100.00                                           |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03            |
      | Line Item 3 Item 1 Quantity | 2                                                |
      | Line Item 3 Item 2 Product  | simple-product-04 - Simple Product 04 - Disabled |
      | Line Item 3 Item 2 Quantity | 3                                                |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Quote Form":
      | simple-product-05 - Simple Product 05 - Deleted |
      | simple-product-01 - Simple Product 01           |
      | simple-product-02 - Simple Product 02           |
    And I should see the "Quote Product Kit Item Line Item Product Ghost Option 1" element in "Line Item 2 Item 2 Product" select in form "Quote Form"
    And I should see the following options for "Line Item 3 Item 2 Product" select in form "Quote Form":
      | simple-product-04 - Simple Product 04 - Disabled |
      | simple-product-01 - Simple Product 01            |
      | simple-product-02 - Simple Product 02            |
    And I should see the "Quote Product Kit Item Line Item Product Ghost Option 1" element in "Line Item 3 Item 2 Product" select in form "Quote Form"

  Scenario: Check the validation errors for kit item line items
    When I click "Submit"
    Then I should see "Price value should not be blank."

    When I fill "Quote Form" with:
      | Line Item 2 Item 2 Price   | 10 |
    And I click "Submit"
    And I should see "Quote Form" validation errors:
      | Line Item 2 Item 2 Product | Original selection no longer available |

    When fill "Quote Form" with:
      | Line Item 2 Item 2 Product | simple-product-02 - Simple Product 02 |
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote Form" validation errors:
      | Line Item 3 Item 2 Product | The selected product is not enabled |

    When fill "Quote Form" with:
      | Line Item 3 Item 2 Product | simple-product-02 - Simple Product 02 |
      | PO Number                  | PO02                                  |
    And click "Submit"
    Then I should see "Quote #2 successfully updated" flash message
    And I should see Quote with:
      | Quote #         | 2     |
      | PO Number       | PO02  |
      | Internal Status | Draft |
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 1] Simple Product 02 | 1 pc or more | $104.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 1] Simple Product 02 | 1 pc or more | $100.00 |

  Scenario: Check original Quote
    When I go to Sales/Quotes
    And click view PO013 in grid
    Then I should see Quote with:
      | PO Number       | PO013            |
      | Internal Status | Sent to customer |
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                            | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                                  | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 05 - Deleted  | 1 pc or more | $104.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 04 - Disabled | 1 pc or more | $100.00 |
