@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPromotionBundle:promotion_crud.yml
Feature: Managing promotions
  In order to use configure promotions discounts
  As an Administrator
  I need to have an ability to specify discount options during promotion creation

  Scenario: Create promotion with Order discount, fixed amount and Euro currency
    Given I login as administrator
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    When I fill "Promotion Form" with:
      | Name       | PR1 |
      | Sort Order | 10  |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value | 10.0 |
      | Currency       | €    |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR1 in grid
    Then I should see promotion with:
      | Discount       | Order        |
      | Type           | Fixed Amount |
      | Discount Value | €10.00       |

  Scenario: Create promotion with Order discount, percent
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    When I fill "Promotion Form" with:
      | Name       | PR2     |
      | Sort Order | 10      |
      | Type       | Percent |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value (%) | 1000000 |
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should be between 0 and 100. |
    When I fill "Promotion Form" with:
      | Discount Value (%) | 20 |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR2 in grid
    Then I should see promotion with:
      | Discount       | Order   |
      | Type           | Percent |
      | Discount Value | 20%     |

  Scenario: Create promotion with Line Item discount, fixed amount, Euro currency, product unit Item, to Each Item
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR3          |
      | Sort Order | 10           |
      | Discount   | Line Item    |
      | Type       | Fixed Amount |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value                     | 10           |
      | Unit of Quantity                   | item         |
      | Apply Discount To                  | Each Item    |
      | Maximum Qty Discount is Applied To | 10           |
      | Currency                           | €            |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR3 in grid
    Then I should see promotion with:
      | Discount                           | Line Item    |
      | Type                               | Fixed Amount |
      | Discount Value                     | €10.00       |
      | Unit of Quantity                   | item         |
      | Apply Discount To                  | Each Item    |
      | Maximum Qty Discount is Applied To | 10           |

  Scenario: Create promotion with Line Item discount, percent, product unit Set, to Line Items Total
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR4       |
      | Sort Order | 10        |
      | Discount   | Line Item |
      | Type       | Percent   |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value (%)                 | 20               |
      | Unit of Quantity                   | set              |
      | Apply Discount To                  | Line Items Total |
      | Maximum Qty Discount is Applied To | 10               |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR4 in grid
    Then I should see promotion with:
      | Discount                           | Line Item        |
      | Type                               | Percent          |
      | Discount Value                     | 20%              |
      | Unit of Quantity                   | set              |
      | Apply Discount To                  | Line Items Total |
      | Maximum Qty Discount is Applied To | 10               |

  Scenario: Create promotion with Buy X Get Y discount, fixed amount, to Each Y Item Separately
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR5                        |
      | Sort Order | 10                         |
      | Discount   | Buy X Get Y (Same Product) |
      | Type       | Fixed Amount               |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Buy X Quantity    | 5                      |
      | Get Y Quantity    | 3                      |
      | Discount Value    | 10                     |
      | Apply Discount To | Each Y Item Separately |
    And I type "2" in "Limit, times"
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR5 in grid
    Then I should see promotion with:
      | Discount          | Buy X Get Y (Same Product) |
      | Type              | Fixed Amount               |
      | Discount Value    | $10.00                     |
      | Buy X Quantity    | 5                          |
      | Get Y Quantity    | 3                          |
      | Apply Discount To | Each Y Item Separately     |
      | Limit, times      | 2                          |

  Scenario: Create promotion with Buy X Get Y discount, percent, product unit Piece, to X + Y Total
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR6                        |
      | Sort Order | 10                         |
      | Discount   | Buy X Get Y (Same Product) |
      | Type       | Percent                    |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Buy X Quantity     | 5           |
      | Get Y Quantity     | 3           |
      | Discount Value (%) | 20          |
      | Unit of Quantity   | piece       |
      | Apply Discount To  | X + Y Total |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR6 in grid
    Then I should see promotion with:
      | Discount          | Buy X Get Y (Same Product) |
      | Type              | Percent                    |
      | Discount Value    | 20%                        |
      | Buy X Quantity    | 5                          |
      | Get Y Quantity    | 3                          |
      | Unit of Quantity  | piece                      |
      | Apply Discount To | X + Y Total                |
      | Limit, times      | N/A                        |

  Scenario: Create promotion with Shipping discount, fixed amount, apply to matching items only, flat rate shipping method
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR7          |
      | Sort Order | 10           |
      | Discount   | Shipping     |
      | Type       | Fixed Amount |
      | Shipping Method   | Flat Rate |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value    | 30 |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR7 in grid
    Then I should see promotion with:
      | Discount          | Shipping     |
      | Type              | Fixed Amount |
      | Discount Value    | $30.00       |
      | Shipping Method   | Flat Rate    |

  Scenario: Create promotion with Shipping discount, percent, apply to shipment, flat rate shipping method
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR8      |
      | Sort Order | 11       |
      | Discount   | Shipping |
      | Type       | Percent  |
      | Shipping Method   | Flat Rate |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Discount Value (%) | 50 |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR8 in grid
    Then I should see promotion with:
      | Discount          | Shipping  |
      | Type              | Percent   |
      | Discount Value    | 50%       |
      | Shipping Method   | Flat Rate |
