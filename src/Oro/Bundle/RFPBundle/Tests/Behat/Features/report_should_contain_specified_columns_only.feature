@regression
@ticket-BAP-9665
@fixture-OroRFPBundle:RFQWorkflows.yml
Feature: Report should contain specified columns only
  In order to simplify reports management
  As an Administrator
  I need to be sure that report page contains only specified columns and no workflow step column

  Scenario: Create report for 'Request For Quote' entity with two fields
    Given I login as administrator
    And I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Request For Quote Report |
      | Entity      | Request For Quote        |
      | Report Type | Table                    |
    And I add the following columns:
      | RFQ #     |
      | PO Number |
    When I save and close form
    Then I should see "Report saved" flash message
    And It should be 2 columns in grid
    And I should see following grid with exact columns order:
      | RFQ # | PO Number |
      | 1     | 0110      |
      | 2     | 0111      |
      | 3     | 0112      |
      | 4     | 0113      |
      | 5     | 0114      |
