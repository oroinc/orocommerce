@ticket-BB-17208
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroOrderBundle:QuoteFixture.yml
@fixture-OroOrderBundle:SalesOrdersQuoteFixture.yml

Feature: Order report with Quote
  In order to build custom report for Order entity
  As an admin
  I should have possibility to use Quote source entity in this report

  Scenario: Create report with quote relation field
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    When I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Order with quote |
      | Entity      | Order            |
      | Report Type | Table            |
    And I add the following columns:
      | PO Number        | None | Order Number |
      | Quote->PO Number | None | Quote Number |
    And I save and close form
    Then I should see "Report saved" flash message
    And there are 2 records in grid
    And I should see following grid:
      | Order Number | Quote Number |
      | ORD1         | PO1          |
      | ORD2         | PO2          |
