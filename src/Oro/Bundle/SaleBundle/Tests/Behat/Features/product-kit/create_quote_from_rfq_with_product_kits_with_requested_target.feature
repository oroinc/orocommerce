@regression
@feature-BB-24496
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Create Quote from RFQ with Product Kits with requested target

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Add product kits to shopping list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "product-kit-01" in "search"
    And I click "Search Button"
    And I click "View Details" for "Product Kit 01" product
    And I click "Configure and Add to Shopping List"
    And I click "Kit Item Line Item 1 Product 1"
    And I click "Kit Item Line Item 2 Product 2"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $129.63 |
    When I click "Add to Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message

    When I click "Configure and Add to Shopping List"
    And I click "Kit Item Line Item 2 Product 2"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $125.93 |
    When I click "Add to Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message

  Scenario: Create RFQ with Product Kits
    When I follow "Shopping List" link within flash message "Product kit has been added to \"Shopping list\""
    And click "Request Quote"
    And I fill form with:
      | PO Number | PO013 |

    And click on "Edit Request Product Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 100 |
    And I should not see "Add Another Line"
    And click "Update Line Item"

    And click on "Edit Request Product Line Item 2"
    And fill "Frontstore RFQ Line Item Form2" with:
      | Target Price | 101 |
    And I should not see "Add Another Line"
    And click "Update Line Item"

    And I click "Submit Request"
    Then I should see "Request has been saved" flash message

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales / Requests For Quote
    And click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU            | Product                                                                                                 | Requested Quantity | Target Price |
      | product-kit-01 | Product Kit 01 Optional Item [piece x 1] Simple Product 03 Mandatory Item [piece x 1] Simple Product 02 | 1 pc               | $100.00      |
      | product-kit-01 | Product Kit 01 Mandatory Item [piece x 1] Simple Product 02                                             | 1 pc               | $101.00      |

  Scenario: Check Quote
    When I click "Create Quote"
    Then "Quote Form" must contains values:
      | Customer                    | Customer1                             |
      | Customer User               | Amanda Cole                           |

      | Line Item 1 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 1 Item 1 Quantity | 1                                     |
      | Line Item 1 Item 1 Price    | 3.70                                  |
      | Line Item 1 Item 2 Product  | simple-product-02 - Simple Product 02 |
      | Line Item 1 Item 2 Quantity | 1                                     |
      | Line Item 1 Item 2 Price    | 2.47                                  |
      | LineItemPrice               | 129.6267                              |

      | Line Item 2 Item 1 Price    |                                       |
      | Line Item 2 Item 2 Product  | simple-product-02 - Simple Product 02 |
      | Line Item 2 Item 2 Quantity | 1                                     |
      | Line Item 2 Item 2 Price    | 2.47                                  |
      | LineItemPrice2              | 125.9267                              |
    And I should not see "Add Offer"
    And I should see "Line Item 1 Offer 1 Remove Button" button disabled
    And I should see "Line Item 2 Offer 1 Remove Button" button disabled
    When I save and close form
    Then I should see "Quote has been saved" flash message
