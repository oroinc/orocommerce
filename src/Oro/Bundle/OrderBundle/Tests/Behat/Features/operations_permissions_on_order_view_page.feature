@regression
@ticket-BB-16518
@fixture-OroOrderBundle:order.yml

Feature: Operations permissions on order view page
  In order to be able to manage order with different permission
  As an administrator
  Check if operations (action button) with enabled and disabled permissions are available to the user.

  Scenario: Check order operation with 'update' permission
    Given I login as administrator
    And go to Sales/Orders
    When I click view SimpleOrder in grid
    Then I should see following buttons:
      | Shipping Tracking    |
      | Add Special Discount |
      | Add Coupon Code      |
      | Cancel               |
      | Close                |
      | Edit                 |

  Scenario: Check order operation without 'update' permission
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Order | Edit:None |
    And I save and close form
    Then I should see "Role saved" flash message
    When I go to Sales/Orders
    And I click view SimpleOrder in grid
    Then I should not see following buttons:
      | Shipping Tracking    |
      | Add Special Discount |
      | Add Coupon Code      |
    # The "edit" button is not unique on the order view page.
    And should not see a "Backend View Order Edit Action Button" element
    And I should see following buttons:
      | Cancel |
      | Close  |

  Scenario: Check order operation without 'cancel orders' permission
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Order | Edit:Global        |
      | Order | Cancel Orders:None |
    And I save and close form
    Then I should see "Role saved" flash message
    When I go to Sales/Orders
    And I click view SimpleOrder in grid
    Then I should not see following buttons:
      | Cancel |
    And I should see following buttons:
      | Shipping Tracking    |
      | Add Special Discount |
      | Add Coupon Code      |
      | Close                |
      | Edit                 |

  Scenario: Check order operation without 'close orders' permission
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Order | Cancel Orders:Global |
      | Order | Close Orders:None    |
    And I save and close form
    Then I should see "Role saved" flash message
    When I go to Sales/Orders
    And I click view SimpleOrder in grid
    Then I should not see following buttons:
      | Close |
    And I should see following buttons:
      | Shipping Tracking    |
      | Add Special Discount |
      | Add Coupon Code      |
      | Cancel               |
      | Edit                 |
