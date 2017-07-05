@fixture-promotion_crud.yml
Feature: Managing promotions
  In order to use promotions on front store
  As an Administrator
  I need to have an ability to create, view, update and delete promotion entity in admin area

  Scenario: Promotion creation
    Given I login as administrator
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    Then I should be on Promotion Create page
    When I save form
    Then I should see "Promotion Form" validation errors:
      | Name           | This value should not be blank. |
      | Sort Order     | This value should not be blank. |
      | Discount Value | This value should not be blank. |
    And I should see "Should be specified filters or added some products manually."
    When I fill "Promotion Form" with:
      | Name                         | Promotion name        |
      | Sort Order                   | 10                    |
      | Enabled                      | 1                     |
      | Stop Further Rule Processing | 1                     |
#      TODO: uncomment after BB-9489
#      | Use Coupons                  | Yes                   |
      | Discount Value               | 10.0                  |
      | Active At (first)            | <DateTime:today>      |
      | Deactivate At (first)        | <DateTime:tomorrow>   |
      | Website                      | Default               |
      | Customer                     | first customer        |
      | Customer Group               | All Customers         |
      | Labels                       | Promotion label       |
      | Descriptions                 | Promotion description |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Promotion edit
    Given I should be on Promotion Edit page
    When I fill "Promotion Form" with:
      | Name | New promotion name |
    And I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Promotion delete
    When I go to Marketing / Promotions / Promotions
    Then number of records should be 1
    And I should see following records in grid:
      | New promotion name |
    When I click delete New promotion name in grid
    And I confirm deletion
    Then I should see "Promotion deleted" flash message
    And there is no records in grid

  Scenario: Create promotion with Order discount, fixed amount and Euro currency
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
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
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
      | Discount Value (%) | 20 |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
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
      | Product Unit                       | item         |
      | Apply Discount To                  | Each Item    |
      | Maximum Qty Discount is Applied To | 10           |
      | Currency                           | €            |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR3 in grid
    Then I should see promotion with:
      | Discount                           | Line Item    |
      | Type                               | Fixed Amount |
      | Discount Value                     | €10.00       |
      | Product Unit                       | item         |
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
      | Product Unit                       | set              |
      | Apply Discount To                  | Line Items Total |
      | Maximum Qty Discount is Applied To | 10               |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR4 in grid
    Then I should see promotion with:
      | Discount                           | Line Item        |
      | Type                               | Percent          |
      | Discount Value                     | 20%              |
      | Product Unit                       | set              |
      | Apply Discount To                  | Line Items Total |
      | Maximum Qty Discount is Applied To | 10               |

  Scenario: Create promotion with Buy X Get Y discount, fixed amount, to Each Y Item Separately
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR5          |
      | Sort Order | 10           |
      | Discount   | Buy X Get Y  |
      | Type       | Fixed Amount |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Buy X             | 5                      |
      | Get Y             | 3                      |
      | Discount Value    | 10                     |
      | Apply Discount To | Each Y Item Separately |
    And I type "2" in "Limit, times"
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR5 in grid
    Then I should see promotion with:
      | Discount          | Buy X Get Y            |
      | Type              | Fixed Amount           |
      | Discount Value    | $10.00                 |
      | Buy X             | 5                      |
      | Get Y             | 3                      |
      | Apply Discount To | Each Y Item Separately |
      | Limit, times      | 2                      |

  Scenario: Create promotion with Buy X Get Y discount, percent, product unit Piece, to X + Y Total
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name       | PR6         |
      | Sort Order | 10          |
      | Discount   | Buy X Get Y |
      | Type       | Percent     |
    And I save form
    Then I should see "Promotion Form" validation errors:
      | Discount Value (%) | This value should not be blank. |
    When I fill "Promotion Form" with:
      | Buy X              | 5           |
      | Get Y              | 3           |
      | Discount Value (%) | 20          |
      | Product Unit       | piece       |
      | Apply Discount To  | X + Y Total |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    And I go to Marketing / Promotions / Promotions
    And I click view PR6 in grid
    Then I should see promotion with:
      | Discount          | Buy X Get Y |
      | Type              | Percent     |
      | Discount Value    | 20%         |
      | Buy X             | 5           |
      | Get Y             | 3           |
      | Product Unit      | piece       |
      | Apply Discount To | X + Y Total |
      | Limit, times      | N/A         |
