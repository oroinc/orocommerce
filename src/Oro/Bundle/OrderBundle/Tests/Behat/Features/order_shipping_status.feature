@fixture-OroOrderBundle:order.yml

Feature: Order shipping status
  In order to manage order shipping status
  As an administrator
  I need to have ability to set shipping status for orders

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Shipping status for existing order
    When go to Sales/Orders
    And click view SimpleOrder in grid
    Then I should see Order with:
      | Shipping Status | N/A |

  Scenario: Shipping status for new order
    When I go to Sales/Orders
    And click "Create Order"
    And I fill "Order Form" with:
      | Customer      | first customer |
      | Customer User | Amanda Cole    |
    And fill "Order Edit Add Line Item Form" with:
      | Product | AA1 |
      | Price   | 50  |
    And click "Add Product"
    And I click "Save and Close"
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see Order with:
      | Shipping Status | Not Shipped |

  Scenario: Change shipping status
    When go to Sales/Orders
    And click edit SimpleOrder in grid
    And fill "Order Form" with:
      | Shipping Status | Shipped |
    And I click "Save and Close"
    Then I should see "Order has been saved" flash message
    And I should see Order with:
      | Shipping Status | Shipped |

  Scenario: Shipping status on order grid
    When go to Sales/Orders
    Then I should see following grid:
      | Order Number | Shipping Status |
      | 4            | Not Shipped     |
      | SecondOrder  |                 |
      | SimpleOrder  | Shipped         |
    When I choose filter for Shipping Status as Is Any Of "Not Shipped"
    Then number of records should be 1
    And I should see following grid:
      | Order Number | Shipping Status |
      | 4            | Not Shipped     |

  Scenario: Verify order shipping status in the storefront
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Order History"
    And I show column "Shipping Status" in "PastOrdersGrid" frontend grid
    Then I should see following "PastOrdersGrid" grid:
      | Order Number | Shipping Status |
      | 4            | Not Shipped     |
      | SecondOrder  |                 |
      | SimpleOrder  | Shipped         |
    When I click view "4" in grid
    Then I should see "Not Shipped"
    When I click "Account Dropdown"
    And I click "Order History"
    When I click view "SecondOrder" in grid
    Then I should not see "Shipping Status"
