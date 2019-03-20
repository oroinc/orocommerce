@regression
@ticket-BAP-9665
@fixture-OroRFPBundle:RFQWorkflows.yml
Feature: Segment should contain specified columns only
  In order to simplify segments management
  As an Administrator
  I need to be sure that segment page contains only specified columns and no workflow step column

  Scenario: Create report for 'Request For Quote' entity with two fields
    Given I login as administrator
    And I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Request For Quote Segment |
      | Entity       | Request For Quote         |
      | Segment Type | Manual                    |
    And I add the following columns:
      | RFQ #     |
      | PO Number |
    When I save and close form
    Then I should see "Segment saved" flash message
    And It should be 2 columns in grid
    And I should see following grid with exact columns order:
      | RFQ # | PO Number |
      | 1     | 0110      |
      | 2     | 0111      |
      | 3     | 0112      |
      | 4     | 0113      |
      | 5     | 0114      |
