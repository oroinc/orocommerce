@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__rfq.yml

Feature: Resubmit RFQ with Product Kits

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Resubmit RFQ
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click view PO013 in grid
    And I click "Cancel"
    Then I should see RFQ status is "Cancelled"
    When I click "Resubmit"
    Then I should see "Your Request For Quote has been successfully resubmitted." flash message

  Scenario: Check original Request
    Given I should see RFQ with data:
      | First Name | Amanda                  |
      | Last Name  | Cole                    |
      | Email      | AmandaRCole@example.org |
      | PO Number  | PO013                   |
    And I should see RFQ status is "Cancelled"
    And I should see next rows in "Storefront Request Line Items Table" table
      | Item                                                                                                                     | Requested Quantity | Target Price |
      | Product Kit 01 Item #: product-kit-01 Mandatory Item 1 piece Simple Product 01                                           | 1 pc               | $124.00      |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 2 pieces Simple Product 02 | 1 pc               | $130.00      |

  Scenario: Check RFQs grid
    When I follow "Back to List"
    Then I should see following grid:
      | Status    | Owner       | PO Number |
      | CANCELLED | Amanda Cole | PO013     |
      | SUBMITTED | Amanda Cole | PO013     |

  Scenario: Check resubmitted Request
    When I click view Submitted in grid
    Then I should see RFQ with data:
      | First Name | Amanda                  |
      | Last Name  | Cole                    |
      | Email      | AmandaRCole@example.org |
      | PO Number  | PO013                   |
    And I should see RFQ status is "Submitted"
    And I should see next rows in "Storefront Request Line Items Table" table
      | Item                                                                                                                     | Requested Quantity | Target Price |
      | Product Kit 01 Item #: product-kit-01 Mandatory Item 1 piece Simple Product 01                                           | 1 pc               | $124.00      |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 2 pieces Simple Product 02 | 1 pc               | $130.00      |

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    And login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status       | PO Number |
      | Amanda Cole  | Open                  | PO013     |
      | Amanda Cole  | Cancelled By Customer | PO013     |
    When click view "Open" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc               | $124.00      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $130.00      |
