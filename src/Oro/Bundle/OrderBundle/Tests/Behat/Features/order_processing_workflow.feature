@fixture-OroOrderBundle:order.yml

Feature: Order Processing Workflow
  In order to manage orders
  As an administrator
  I need to have ability to use "Order Processing" workflow

  Scenario: Background
    Given I login as administrator
    When I go to System/ Workflows
    And I click Activate "Order Processing" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Decline order
    When I go to Sales/Orders
    And click "Create Order"
    And click "Add Product"
    And I fill "Order Form" with:
      | Customer | first customer |
      | Product  | AA1            |
      | Price    | 50             |
    And I click "Save and Close"
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see that order internal status is "Pending"
    And I should see Order with:
      | Shipping Status | Not Shipped |
    And I should see "New" in the "Workflow Current Step" element
    And I should see following buttons:
      | Delete |
      | Edit   |
    And I should not see following buttons:
      | Cancel |
      | Close  |
    When I click "Decline"
    And I should see that order internal status is "Cancelled"
    And I should see "Declined" in the "Workflow Current Step" element
    And I should see following buttons:
      | Delete |
    And I should not see following buttons:
      | Edit   |
      | Cancel |
      | Close  |

  Scenario: Complete order
    When I go to Sales/Orders
    And click "Create Order"
    And click "Add Product"
    And I fill "Order Form" with:
      | Customer | first customer |
      | Product  | AA1            |
      | Price    | 50             |
    And I click "Save and Close"
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see that order internal status is "Pending"
    And I should see Order with:
      | Shipping Status | Not Shipped |
    And I should see "New" in the "Workflow Current Step" element
    And I should see following buttons:
      | Delete |
      | Edit   |
    And I should not see following buttons:
      | Cancel |
      | Close  |
    When I click "Confirm"
    And I should see that order internal status is "Open"
    And I should see "Confirmed" in the "Workflow Current Step" element
    And I should see following buttons:
      | Delete |
      | Edit   |
    And I should not see following buttons:
      | Cancel |
      | Close  |
    When I click "Process"
    And I should see that order internal status is "Processing"
    And I should see "Processing" in the "Workflow Current Step" element
    And I should see Order with:
      | Shipping Status | Not Shipped |
    And I should see following buttons:
      | Delete |
      | Edit   |
    And I should not see following buttons:
      | Cancel |
      | Close  |
    When I click "Mark As Shipped"
    And I should see that order internal status is "Processing"
    And I should see "Processing" in the "Workflow Current Step" element
    And I should see Order with:
      | Shipping Status | Shipped |
    And I should see following buttons:
      | Delete |
      | Edit   |
    And I should not see following buttons:
      | Cancel |
      | Close  |
    When I click "Complete"
    And I should see that order internal status is "Closed"
    And I should see "Completed" in the "Workflow Current Step" element
    And I should see Order with:
      | Shipping Status | Shipped |
    And I should see following buttons:
      | Delete |
    And I should not see following buttons:
      | Edit   |
      | Cancel |
      | Close  |
