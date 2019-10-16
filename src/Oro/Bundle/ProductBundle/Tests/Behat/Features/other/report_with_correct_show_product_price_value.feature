@ticket-BB-17556
@fixture-OroProductBundle:products.yml

Feature: Report with correct show product price value
  In order to use custom reports with prices for product entity
  As an Administrator
  I should be able to see product price value without symbol in custom report

  Scenario: See product price value without symbol in custom report grid
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    And click "Create Report"
    When I fill form with:
      | Name          | test     |
      | Entity        | Product  |
      | Report Type   | Table    |
    And I add the following columns:
      | SKU                            |
      | Product (Product Price)->Value |
    And I save and close form
    Then I should see "Report saved" flash message
    And I should see following grid:
      | Value   |
      | 10      |
