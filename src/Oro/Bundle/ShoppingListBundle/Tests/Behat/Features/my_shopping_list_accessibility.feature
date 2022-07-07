@ticket-BB-18916
@ticket-BB-20327
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List Accessibility
  As a User
  I want to be sure that datagrids are accessible via keyboard

  Scenario: Navigation inside of grid by Arrow Right key
    Given I login as AmandaRCole@example.org buyer
    Then follow "Account"
    And I click on "Shopping Lists Navigation Link"
    And I click Edit "Shopping List 1" in grid
    When I focus on "Shopping List Edit Grid Select Row 1 Input"
    Then I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Input" element
    And I should see "Shopping List Edit Grid Select Row 1 Sku" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Sku" element
    Then I should see "Shopping List Edit Grid Select Row 1 Item" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Item" element
    Then I should see "Shopping List Edit Grid Select Row 1 Status" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Status" element
    Then I should see "Shopping List Edit Grid Select Row 1 Qty" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Qty" element
    Then I should see "Shopping List Edit Grid Select Row 1 Price" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Price" element
    Then I should see "Shopping List Edit Grid Select Row 1 Subtotal" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Subtotal" element
    Then I should see "Shopping List Edit Grid Select Row 1 Delete" element focused
    When I press "ArrowRight" key on "Shopping List Edit Grid Select Row 1 Delete" element
    Then I should see "Shopping List Edit Grid Select Row 1 Delete" element focused

  Scenario: Navigation inside of grid by Arrow Right key
    Given I focus on "Shopping List Edit Grid Select Row 1 Delete"
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Delete" element
    Then I should see "Shopping List Edit Grid Select Row 1 Subtotal" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Subtotal" element
    Then I should see "Shopping List Edit Grid Select Row 1 Price" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Price" element
    Then I should see "Shopping List Edit Grid Select Row 1 Qty" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Qty" element
    Then I should see "Shopping List Edit Grid Select Row 1 Status" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Status" element
    Then I should see "Shopping List Edit Grid Select Row 1 Item" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Item" element
    Then I should see "Shopping List Edit Grid Select Row 1 Sku" element focused
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Sku" element
    Then I should see focus within "Shopping List Edit Grid Select Row 1 Mass Action" element
    When I press "ArrowLeft" key on "Shopping List Edit Grid Select Row 1 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 1 Mass Action" element

  Scenario: Navigation inside of grid by Down / Up arrows keys
    Given I focus on "Shopping List Edit Grid Select Row 1 Input"
    When I press "ArrowDown" key on "Shopping List Edit Grid Select Row 1 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 2 Mass Action" element
    When I press "ArrowDown" key on "Shopping List Edit Grid Select Row 2 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 3 Mass Action" element
    When I press "ArrowDown" key on "Shopping List Edit Grid Select Row 3 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 4 Mass Action" element
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 4 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 3 Mass Action" element
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 3 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 2 Mass Action" element
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 2 Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 1 Mass Action" element
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 1 Mass Action" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 1 Mass Action" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused

  Scenario: Navigation inside grid cell by (Space / Enter) arrows keys
    Given I focus on "Shopping List Edit Grid Select Row 1 Input"
    When I press "ArrowUp" key on "Shopping List Edit Grid Select Row 1 Mass Action" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused
    When I press "Space" key on "Shopping List Edit Grid Header Mass Action" element
    Then I should see "Shopping List Edit Grid Header Input" element focused
    When I press "Esc" key on "Shopping List Edit Grid Header Input" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused
    When I press "Enter" key on "Shopping List Edit Grid Header Mass Action" element
    Then I should see "Shopping List Edit Grid Header Input" element focused
    When I press "Esc" key on "Shopping List Edit Grid Header Input" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused

  Scenario: Navigation inside of grid by End / Home arrows keys
    Given I focus on "Shopping List Edit Grid Select Row 1 Input"
    When I press "End" key on "Shopping List Edit Grid Select Row 1 Input" element
    Then I should see focus within "Shopping List Edit Grid Select Row 1 Action" element
    When I press "Home" key on "Shopping List Edit Grid Select Row 1 Delete" element
    Then I should see focus within "Shopping List Edit Grid Select Row 1 Mass Action" element
    When I press "End" key on "Shopping List Edit Grid Select Row 1 Input" element
    Then I should see "Shopping List Edit Grid Select Row 1 Delete" element focused
    When I press "Home" key on "Shopping List Edit Grid Select Row 1 Delete" element
    Then I should see "Shopping List Edit Grid Select Row 1 Input" element focused

  Scenario: Navigation inside of grid by Ctrl + (End / Home) arrows keys
    Given I focus on "Shopping List Edit Grid Select Row 1 Input"
    When I press "ctrl+Home" key on "Shopping List Edit Grid Select Row 1 Input" element
    Then I should see "Shopping List Edit Grid Header Mass Action" element focused
    When I press "ctrl+End" key on "Shopping List Edit Grid Header Mass Action" element
    Then I should see focus within "Shopping List Edit Grid Select Row 25 Action" element

  Scenario: Navigation inside of grid by (Page Up / Page Down) arrows keys
    Given I focus on "Shopping List Edit Grid Select Row 1 Input"
    When I press "PageDown" key on "Shopping List Edit Grid Select Row 1 Input" element
    Then I should see focus within "Frontend Customer User Shopping List Edit Grid" element
    And I should see following grid containing rows:
      | SKU  | Item                    |          | Qty Update All | Price  | Subtotal |
      | CC29 | Product 29 Note 29 text | In Stock | 13 piece       | $31.00 | $403.00  |
      | CC30 | Product 30 Note 30 text | In Stock | 13 piece       | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text | In Stock | 13 piece       | $31.00 | $403.00  |
    And I press "PageUp" key on "Shopping List Edit Grid Header Mass Action" element
    Then I should see focus within "Frontend Customer User Shopping List Edit Grid" element
    And I should see following grid containing rows:
      | SKU  | Item                               |              | Qty Update All | Price  | Subtotal              |
      | BB04 | Configurable Product 1 Note 4 text | In Stock     | 3 item         | $11.00 | $33.00 -$16.50 $16.50 |
      | BB05 | Configurable Product 1 Note 5 text | Out of Stock | 3 item         | $11.00 | $33.00 -$16.50 $16.50 |
      | BB06 | Configurable Product 2 Note 6 text | In Stock     | 3 item         | $11.00 | $33.00 -$16.50 $16.50 |
      | BB07 | Configurable Product 2 Note 7 text | Out of Stock | 5 piece        | $17.00 | $85.00                |

  Scenario: Delete product by keyboard
    Given I focus on "Shopping List Edit Grid Select Row 1 Delete"
    When I press "Space" key on "Shopping List Edit Grid Select Row 1 Delete" element
    And I should see focus within "UiWindow" element
    When I press "Space" key on "UiWindow okButton" element
    Then I should see focus within "Frontend Customer User Shopping List Edit Grid" element
    And I should see "Shopping List Edit Grid Select Row 1 Delete" element focused
    And I should see "Summary 31 Items"

  Scenario: Edit product note by keyboard
    Given I focus on "Shopping List Edit Grid Edit Note"
    When I press "Space" key on "Shopping List Edit Grid Edit Note" element
    And I should see focus within "UiWindow" element
    And I fill in "Shopping List Notes in Modal" with "Update Note 5 text"
    When I press "Space" key on "UiWindow okButton" element
    Then I should see focus within "Frontend Customer User Shopping List Edit Grid" element
    And I should see "Shopping List Edit Grid Select Row 1 Item" element focused
    Then I should see following grid containing rows:
      | SKU  | Item                                      |              | Qty Update All | Price  | Subtotal              |
      | BB05 | Configurable Product 1 Update Note 5 text | Out of Stock | 3 item         | $11.00 | $33.00 -$16.50 $16.50 |
