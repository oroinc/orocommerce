@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/create_order_from_rfq_with_product_kits__rfq.yml

Feature: Create Order from RFQ with Product Kits

  Scenario: Feature Background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 01 | 1 pc               | $104.69      |

  Scenario: Create Order
    When I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | Customer                 | Customer1                                                   |
      | Customer User            | Amanda Cole                                                 |
      | Billing Address          | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address         | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Product                  | simple-product-01 - Simple Product 01                       |
      | Quantity                 | 1                                                           |
      | Price                    | 1.2345                                                      |
      | Product2                 | product-kit-01 - Product Kit 01                             |
      | Quantity2                | 1                                                           |
      | Price2                   | 134.5667                                                    |
      | Product2KitItem1Product  | simple-product-03 - Simple Product 03                       |
      | Product2KitItem1Quantity | 2                                                           |
      | Product2KitItem1Price    | 3.7                                                         |
      | Product2KitItem2Product  | simple-product-01 - Simple Product 01                       |
      | Product2KitItem2Quantity | 3                                                           |
      | Product2KitItem2Price    | 1.23                                                        |
    And I see next subtotals for "Backend Order":
      | Subtotal | $135.80 |
      | Total    | $135.80 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And "Order Form" must contains values:
      | Customer                 | Customer1                                                   |
      | Customer User            | Amanda Cole                                                 |
      | Billing Address          | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address         | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Product                  | simple-product-01 - Simple Product 01                       |
      | Quantity                 | 1                                                           |
      | Price                    | 1.2345                                                      |
      | Product2                 | product-kit-01 - Product Kit 01                             |
      | Quantity2                | 1                                                           |
      | Price2                   | 134.5667                                                    |
      | Product2KitItem1Product  | simple-product-03 - Simple Product 03                       |
      | Product2KitItem1Quantity | 2                                                           |
      | Product2KitItem1Price    | 3.7000                                                      |
      | Product2KitItem2Product  | simple-product-01 - Simple Product 01                       |
      | Product2KitItem2Quantity | 3                                                           |
      | Product2KitItem2Price    | 1.2300                                                      |
