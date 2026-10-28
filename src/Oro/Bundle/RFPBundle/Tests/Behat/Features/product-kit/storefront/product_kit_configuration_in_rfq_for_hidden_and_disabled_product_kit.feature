@regression
@ticket-BB-27684
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Product kit configuration in RFQ for hidden and disabled product kit

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Add Product Kit Line Item to the RFQ
    Given I proceed as the Buyer
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
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Price                | Total: $124.6867                     |
    When I click "Save"
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element

  Scenario: Hide the Product Kit
    Given I proceed as the Admin
    When I login as administrator
    And go to Products / Products
    And click "View" on row "product-kit-01" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Configure the hidden Product Kit
    Given I proceed as the Buyer
    When I click "Edit Request Product Line Item"
    And I click "RFQ Kit Item Line Item 1 Configure Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title | N/A        |
      | Price | Total: N/A |
    And I should not see "Product Kit 01" in the "Product Kit Dialog" element
    And I should not see "Optional Item" in the "Product Kit Dialog" element
    And I should not see "Mandatory Item" in the "Product Kit Dialog" element
    And I should not see "simple-product-01" in the "Product Kit Dialog" element
    And I should not see "$124.6867" in the "Product Kit Dialog" element
    And I should see "Product cannot be empty" in the "Product Kit Dialog" element
    And I should see "Original selection no longer available" in the "Product Kit Dialog" element
    And I close ui dialog

  Scenario: Make the Product Kit visible and disable it
    Given I proceed as the Admin
    When I click "More actions"
    And click "Manage Visibility"
    And I select "Visible" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message
    When click "Edit"
    And fill form with:
      | Status | Disable |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Configure the disabled Product Kit
    Given I proceed as the Buyer
    When I click "RFQ Kit Item Line Item 1 Configure Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title | N/A        |
      | Price | Total: N/A |
    And I should not see "Product Kit 01" in the "Product Kit Dialog" element
    And I should not see "Optional Item" in the "Product Kit Dialog" element
    And I should not see "Mandatory Item" in the "Product Kit Dialog" element
    And I should not see "simple-product-01" in the "Product Kit Dialog" element
    And I should not see "$124.6867" in the "Product Kit Dialog" element
    And I should see "Product cannot be empty" in the "Product Kit Dialog" element
    And I should see "Original selection no longer available" in the "Product Kit Dialog" element
