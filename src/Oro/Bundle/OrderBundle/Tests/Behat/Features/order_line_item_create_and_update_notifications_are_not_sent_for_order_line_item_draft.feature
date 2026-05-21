@feature-BB-26023
@ticket-BB-27242
@fixture-OroOrderBundle:OrderLineItemCreateAndUpdateNotificationsAreNotSentForOrderLineItemDraftFixture.yml

Feature: Order line item create and update notifications are not sent for order line item draft
  In order to avoid receiving premature email notifications when editing order line items
  As an administrator
  I need order line item email notifications to be sent only after the order is saved, not after each draft change

  Scenario: Feature Background
    Given I login as administrator
    When I go to System/Emails/Notification Rules
    And I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | Order Line Item                          |
      | Event Name  | Entity create                            |
      | Template    | order_line_item_create_notification_test |
      | Groups      | Administrators                           |
    And I save and close form
    Then I should see "Notification Rule saved" flash message
    When I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | Order Line Item                          |
      | Event Name  | Entity update                            |
      | Template    | order_line_item_update_notification_test |
      | Groups      | Administrators                           |
    And I save and close form
    Then I should see "Notification Rule saved" flash message

  Scenario: No email is sent when adding a draft line item to the order edit page
    When I go to Sales/Orders
    And click edit SimpleOrder in grid
    And fill "Order Line Item Draft Create Form" with:
      | Product | AA1 |
      | Price   | 30  |
    And click on "Order Line Item Draft Create Form Add Product"
    Then email with Subject "Order Line Item Create Test Notification" was not sent

  Scenario: No email is sent when modifying a draft line item on the order edit page
    When I click "Line Items"
    And I click edit AA1 in grid
    And fill "Order Line Item Draft Edit Form" with:
      | Price | 80 |
    And I click on "Order Line Item Draft Edit Form Save Button"
    Then email with Subject "Order Line Item Update Test Notification" was not sent

  Scenario: Emails are sent after the order is saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And email with Subject "Order Line Item Create Test Notification" containing the following was sent:
      | To | admin@example.com |
    And email with Subject "Order Line Item Update Test Notification" containing the following was sent:
      | To | admin@example.com |
