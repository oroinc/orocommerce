@regression
@ticket-BB-25261
@fixture-OroRFPBundle:create_order_and_quote_from_rfq_with_deleted_product.yml

Feature: Create Order and Quote from RFQ with deleted product
  In order to handle RFQs referencing deleted products
  As an administrator
  I should be able to create order and quote with deleted product as a free-form item

  Scenario: Delete products referenced by RFQs
    Given I login as administrator
    When I go to Products/ Products
    And I click delete PRODUCT_TO_DELETE in grid
    Then I should see "Are you sure you want to delete this Product?"
    And I click "Yes, Delete"
    Then I should see "Product deleted" flash message
    When I click delete KIT_TO_DELETE in grid
    Then I should see "Are you sure you want to delete this Product?"
    And I click "Yes, Delete"
    Then I should see "Product deleted" flash message

  Scenario: Create Order from RFQ with deleted product
    When I go to Sales/ Requests For Quote
    And I click view "PO-ORDER" in grid
    And I click on "RFQ Create Order"
    # Free-form items inherit the RFQ currency and start with a zero placeholder price
    And I click Edit "PRODUCT_TO_DELETE" in grid
    Then "Order Form" must contains values:
      | LineItemFreeFormProduct | PRODUCT_TO_DELETE |
      | LineItemFreeFormSku     | PRODUCT_TO_DELETE |
      | Price                   | 0.00              |
    And I click on the first "Order Edit Save Changes"
    And I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Create Quote from RFQ with deleted product
    When I go to Sales/ Requests For Quote
    And I click view "PO-QUOTE" in grid
    And I click "Create Quote"
    Then "Quote Form" must contains values:
      | LineItemFreeFormProduct | PRODUCT_TO_DELETE |
      | LineItemFreeFormSku     | PRODUCT_TO_DELETE |
    When fill "Quote Form" with:
      | LineItemPrice | 10 |
    And I save and close form
    And I click "Save on conf window"
    Then I should see "Quote has been saved" flash message

  Scenario: Create Order from RFQ with deleted product kit
    When I go to Sales/ Requests For Quote
    And I click view "PO-KIT-ORDER" in grid
    And I click on "RFQ Create Order"
    # Free-form items inherit the RFQ currency and start with a zero placeholder price
    And I click Edit "KIT_TO_DELETE" in grid
    Then "Order Form" must contains values:
      | LineItemFreeFormProduct | KIT_TO_DELETE |
      | LineItemFreeFormSku     | KIT_TO_DELETE |
      | Price                   | 0.00          |
    And I click on the first "Order Edit Save Changes"
    And I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
