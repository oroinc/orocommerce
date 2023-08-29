@regression
@ticket-BAP-22098
@fixture-OroProductBundle:report_products.yml
@fixture-OroReportBundle:export_report_with_grouping.yml

Feature: Export Product Report

  Scenario: Feature Background
    Given I login as administrator
    And I change the export batch size to 3

  Scenario: Export Products Report Datagrid
    When I go to Reports & Segments / Manage Custom Reports
    And I click View Products in grid
    Then I click "Export Grid"
    And I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | SKU     |
      | PSKU-1  |
      | PSKU-2  |
      | PSKU-3  |
      | PSKU-4  |
      | PSKU-5  |
      | PSKU-6  |
      | PSKU-7  |
      | PSKU-8  |
      | PSKU-9  |
      | PSKU-10 |
