@regression
@ticket-BB-22310
@fixture-OroOrderBundle:export_report_with_order_and_grouping.yml

Feature: Export Report with Order entity and grouping
  Check if reports with orders that have groupings are exported correctly

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Check Report Datagrid
    Given I go to Reports & Segments / Manage Custom Reports
    When I click View Orders in grid
    Then I should see following grid containing rows:
      | Order Number |
      | Second Order |
      | First Order  |

  Scenario: Export Report Datagrid
    Given I click "Export Grid"
    When I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    # There's no point in checking all the columns, the problem was that the export failed.
    And exported file contains at least the following columns:
      | Order Number |
      | Second Order |
      | First Order  |
