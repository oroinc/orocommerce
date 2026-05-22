@feature-BB-26023
@ticket-BB-27242
@fixture-OroOrderBundle:OrderCreateAndUpdateNotificationsAreNotSentForOrderDraftFixture.yml

Feature: Order create and update notifications are not sent for order draft
  In order to avoid receiving premature email notifications
  As an administrator
  I need order email notifications to be sent only after the order is actually saved

  Scenario: Feature Background
    Given I login as administrator
    When I go to System/Emails/Notification Rules
    And I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | Order                    |
      | Event Name  | Entity create            |
      | Template    | order_confirmation_email |
      | Users       | John Doe                 |
    And I save and close form
    Then I should see "Notification Rule saved" flash message
    When I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | Order                          |
      | Event Name  | Entity update                  |
      | Template    | order_update_notification_test |
      | Users       | John Doe                       |
    And I save and close form
    Then I should see "Notification Rule saved" flash message

  Scenario: No email is sent when clicking the Create Order button before the order is saved
    When I go to Sales/Orders
    And I click "Create Order"
    Then email with Subject "Your Store Name order has been received." was not sent

  Scenario: Email is sent after the new order is saved
    When I fill "Order Form" with:
      | Customer      | first customer |
      | Customer User | Amanda Cole    |
    And fill "Order Line Item Draft Create Form" with:
      | Product | AA1 |
      | Price   | 50  |
    And click on "Order Line Item Draft Create Form Add Product"
    And I click "Save and Close"
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And email with Subject "Your Store Name order has been received." containing the following was sent:
      | To | admin@example.com |

  Scenario: No email is sent when making draft line item changes on the order edit page
    When I go to Sales/Orders
    And click edit SimpleOrder in grid
    And fill "Order Form" with:
      | Shipping Status | Shipped |
    Then email with Subject "Order Update Test Notification" was not sent

  Scenario: Email is sent after the existing order is saved
    When I save form
    Then I should see "Order has been saved" flash message
    And email with Subject "Order Update Test Notification" containing the following was sent:
      | To | admin@example.com |
