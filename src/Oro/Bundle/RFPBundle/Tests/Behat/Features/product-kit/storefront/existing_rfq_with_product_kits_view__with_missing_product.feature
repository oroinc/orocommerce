@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml
@fixture-OroRFPBundle:product-kit/storefront/existing_rfq_with_product_kits_view__with_missing_product__rfq.yml

Feature: Existing RFQ with Product Kits view - with Missing Product

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

  Scenario: View Request
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click view PO013 in grid
    Then I should see RFQ with data:
      | Contact Person | Amanda Cole             |
      | Email Address  | AmandaRCole@example.org |
      | PO Number      | PO013                   |
    And I should see next rows in "Storefront Request Line Items Table" table
      | Item                                                                                                                                | Requested Quantity | Target Price |
      | Simple Product 01 Item #: simple-product-01                                                                                         | 1 pc               | $2.00        |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 3 pieces Simple Product 05 - Deleted  | 1 pc               | $104.69      |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 3 pieces Simple Product 04 - Disabled | 1 pc               | $100.00      |
    And I should see a "Simple Product 03 Link" element
    And I should not see a "Simple Product 05 - Deleted Link" element
    And I should not see a "Simple Product 04 - Disabled Link" element
