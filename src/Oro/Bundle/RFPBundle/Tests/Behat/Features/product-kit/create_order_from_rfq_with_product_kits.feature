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
      | Customer         | Customer1                                                   |
      | Customer User    | Amanda Cole                                                 |
      | Billing Address  | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see following grid:
      | SKU               | Product                                                                                                                 | Quantity | Price     |
      | simple-product-01 | Simple Product 01                                                                                                       | 1 piece  | $1.2345   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] $3.7035 Simple Product 03 Mandatory Item [piece x 3] $1.2345 Simple Product 01 | 1 piece  | $134.5667 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $135.80 |
      | Total    | $135.80 |

  Scenario: Check that order can be saved
    When I save form
    Then I should see "Order has been saved" flash message
    And "Order Form" must contains values:
      | Customer                 | Customer1                                                   |
      | Customer User            | Amanda Cole                                                 |
      | Billing Address          | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address         | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see following grid:
      | SKU               | Product                                                                                                                 | Quantity | Price     |
      | simple-product-01 | Simple Product 01                                                                                                       | 1 piece  | $1.2345   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] $3.7035 Simple Product 03 Mandatory Item [piece x 3] $1.2345 Simple Product 01 | 1 piece  | $134.5667 |
