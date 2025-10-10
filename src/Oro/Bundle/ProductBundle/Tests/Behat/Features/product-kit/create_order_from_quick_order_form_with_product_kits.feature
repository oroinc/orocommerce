@feature-BB-22738
@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml

Feature: Create Order from Quick order form with Product Kits

  Scenario: Add Product Kit inline
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Order Form" with:
      | SKU1 | product-kit-01 |
    And I wait for products to load
    And I click on empty space
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01 |
      | QTY1      | 1                               |
      | UNIT1     | piece                           |
      | SUBTOTAL1 | N/A                             |
    And I should see that "Quick Add Form Validation Row1 Warning" contains "This product cannot be added through the quick order form. Use the product details page instead."
    When I click "Create Order"
    Then I should see "Some selected items need a quick review. Update or remove them to proceed to checkout." flash message
    And I close all flash messages

  Scenario: Add Product Kit via copy-paste
    When I click on "Quick Order Form > DeleteRow1"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | product-kit-01,1 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU2      | PRODUCT-KIT-01 - Product Kit 01 |
      | QTY2      | 1                               |
      | UNIT2     | piece                           |
      | SUBTOTAL2 | N/A                             |
    And I should see that "Quick Add Form Validation Row2 Error" contains "This product cannot be added through the quick order form. Use the product details page instead."
    When I click "Create Order"
    Then I should see "Some selected items need a quick review. Update or remove them to proceed to checkout." flash message
    And I close all flash messages

  Scenario: Import Product Kit via file
    When I click on "Quick Order Form > DeleteRow2"
    And I click "What File Structure Is Accepted"
    Then I should see that "UiDialog Title" contains "Import Excel .CSV File"
    When I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number    | Quantity | Unit  |
      | product-kit-01 | 1        | piece |
    And I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU3      | PRODUCT-KIT-01 - Product Kit 01 |
      | QTY3      | 1                               |
      | UNIT3     | piece                           |
      | SUBTOTAL3 | N/A                             |
    And I should see that "Quick Add Form Validation Row3 Error" contains "This product cannot be added through the quick order form. Use the product details page instead."
    When I click "Create Order"
    Then I should see "Some selected items need a quick review. Update or remove them to proceed to checkout." flash message
    And I close all flash messages
