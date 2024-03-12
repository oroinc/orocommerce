@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_wittingly_invalid__order.yml

Feature: Existing Order with Product Kits Validation - with Wittingly Invalid

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check that order can be saved with an untouched wittingly invalid line item
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check that order cannot be saved with an updated wittingly invalid line item
    When fill "Order Form" with:
      | ProductKitItem1Price | 35.56 |
      | ProductKitItem2Price | 24.45 |
    And I save form
    And I click "Save" in modal window
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | Only whole numbers are allowed for unit "piece"; The quantity should be between 0 and 5  |
      | ProductKitItem2Quantity | Only whole numbers are allowed for unit "piece"; The quantity should be between 1 and 10 |
