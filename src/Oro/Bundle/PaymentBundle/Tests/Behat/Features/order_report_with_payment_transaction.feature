@ticket-BB-17209
@fixture-OroOrderBundle:order.yml
@fixture-OroPaymentBundle:OrderPaymentTransactions.yml

Feature: Order report with Payment Transaction
  In order to build custom report for Order entity
  As an admin
  I should have possibility to use Payment Transaction related entity

  Scenario: Create report with Payment Transaction relation
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    When I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Order with Payment Transaction |
      | Entity      | Order                          |
      | Report Type | Table                          |
    And I add the following columns:
      | PO Number                           | None | Order Number   |
      | Payment Transaction->Amount         | None | Amount         |
      | Payment Transaction->Currency       | None | Currency       |
      | Payment Transaction->Payment Method | None | Payment Method |
    And I save and close form
    Then I should see "Report saved" flash message
    And there are 3 records in grid
    And I should see following grid containing rows:
      | Order Number | Amount | Currency | Payment Method |
      | ORD1         | 2      | USD      | payment_term_4 |
      | ORD1         | 3      | USD      | payment_term_4 |
      | ORD2         |        |          |                |
