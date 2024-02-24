@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits_validation__with_different_min_max_quantity__product.yml

Feature: Create RFQ with Product Kits Validation - with Different Min Max Quantity

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ from scratch
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And fill form with:
      | PO Number | PO013 |
    And fill "Frontend Request Form" with:
      | Line Item Product | product-kit-01 - Product Kit 01 |
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Price as configured: $127.1567       |
      | okButton             | Save                                 |
    When I click "RFQ Kit Item Line Item 1 Product 1"
    Then "RFQ Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 1 Quantity | 2 |
      | Kit Item Line Item 2 Quantity | 3 |

  Scenario Outline: Set product kit line item quantity with violation
    When fill "RFQ Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | <Item 1 Quantity> |
      | Kit Item Line Item 2 Quantity | <Item 2 Quantity> |
    And click "Save"
    Then I should see "RFQ Product Kit Line Item Form" validation errors:
      | Kit Item Line Item 1 Quantity | <Item 1 Quantity validation message> |
      | Kit Item Line Item 2 Quantity | <Item 2 Quantity validation message> |

    Examples:
      | Item 1 Quantity | Item 2 Quantity | Item 1 Quantity validation message     | Item 2 Quantity validation message      |
      | 6               | 11              | The quantity should be between 2 and 5 | The quantity should be between 3 and 10 |
      | 1               | 2               | The quantity should be between 2 and 5 | The quantity should be between 3 and 10 |

  Scenario: Add Product Kit Line Item to the RFQ
    When fill "RFQ Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 2 |
      | Kit Item Line Item 2 Quantity | 3 |
    And click "Save"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 3 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element

  Scenario Outline: Set not valid product kit line item quantity without violation
    When click on "RFQ Kit Item Line Item 1 Configure Button"
    And fill "RFQ Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | <Item 1 Quantity> |
      | Kit Item Line Item 2 Quantity | <Item 2 Quantity> |
    And click "Save"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 3 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element

    Examples:
      | Item 1 Quantity | Item 2 Quantity |
      | invalid         | invalid         |
      |                 |                 |
      | 0               | 0               |
      | 2.45            | 3.34            |

  Scenario: Update min quantity for Product Kit items
    Given I proceed as the Admin
    And I login as administrator
    When go to Products / Products
    And click edit "product-kit-01" in grid
    And I fill "ProductKitForm" with:
      | Kit Item 1 Minimum Quantity | 3 |
    And I click "Kit Item 2 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 2 Minimum Quantity | 4 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Try to submit the Request
    Given I continue as the Buyer
    When click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 3 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    When I click "Submit Request"
    Then I should not see "Request has been saved" flash message
    And I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 The quantity should be between 3 and 5 Mandatory Item 3 piece simple-product-01 - Simple Product 01 The quantity should be between 4 and 10 The selected kit configuration is not valid. Please modify or remove it." in the "RFQ Products List Line Item 1" element
