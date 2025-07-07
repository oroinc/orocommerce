@regression
@fixture-OroRFPBundle:RFQ_with_removed_unit.yml

Feature: RFQ and Quote with Project Name
  In order to assign RFQs and quotes to a project
  As a Buyer and an Administrator
  I should be able to manage a project name for RFQs and quotes

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I enable configuration options:
      | oro_rfp.enable_rfq_project_name    |
      | oro_sale.enable_quote_project_name |

  Scenario: Create RFQ with project name in storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill form with:
      | Project Name | Some Project |
      | First Name   | Amanda       |
      | Last Name    | Cole         |
      | PO Number    | PO001        |
    And I open select entity popup for field "Line Item Product" in form "Frontend Request Form"
    And I click on SKU123 in grid
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And I should see RFQ with data:
      | Project Name   | Some Project |
      | Contact Person | Amanda Cole  |
      | PO Number      | PO001        |
    When I click "Account Dropdown"
    And I click "Requests For Quote"
    Then I should see following grid:
      | RFQ # | Project Name | PO Number |
      | 1     |              | 0111      |
      | 2     | Some Project | PO001     |
    When I set filter Project Name as contains "Some Project"
    Then I should see following grid:
      | RFQ # | Project Name | PO Number |
      | 2     | Some Project | PO001     |

  Scenario: Check RFQ with project name in backoffice
    Given I proceed as the Admin
    When I go to Sales/ Requests For Quote
    Then I should see following grid:
      | RFQ # | Project Name | PO Number |
      | 2     | Some Project | PO001     |
      | 1     |              | 0111      |
    When I set filter Project Name as contains "Some Project"
    Then I should see following grid:
      | RFQ # | Project Name | PO Number |
      | 2     | Some Project | PO001     |
    When I click view "PO001" in grid
    Then I should see RFQ with:
      | PO Number | PO001 |
    And I should see "RFQ #2: Some Project"
    When I click "Edit"
    And I fill form with:
      | Project Name | Another Project |
    And I save and close form
    Then I should see "RFQ #2: Another Project"

  Scenario: Create Quote from RFQ with project name in backoffice
    When I click "Create Quote"
    Then "Quote Form" must contains values:
      | Project Name | Another Project |
      | PO Number    | PO001           |
    When I fill "Quote Form" with:
      | Line Item 1 Price | 10 |
    And I save and close form
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see quote with:
      | PO Number | PO001                   |
      | Request   | RFQ #2: Another Project |
    And I should see "Quote #1: Another Project"
    When I click "Send to Customer"
    And I click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Check Quote with project name in storefront
    Given I continue as the Buyer
    When I click "Account Dropdown"
    And I click "Quotes"
    Then I should see following grid:
      | Quote # | Project Name    | PO Number |
      | 1       | Another Project | PO001     |
    When I click view "PO001" in grid
    Then I should see Quote Frontend Page with data:
      | Request      | Request For Quote #2 |
      | Project Name | Another Project      |

  Scenario: Check RFQ and Quote with project name but when "Project Name" config option is disabled
    Given I continue as the Admin
    And I disable configuration options:
      | oro_rfp.enable_rfq_project_name    |
      | oro_sale.enable_quote_project_name |
    When I go to Sales/ Requests For Quote
    Then I should not see "Project Name"
    When I click view "PO001" in grid
    Then I should not see "Another Project"
    When I go to Sales/ Quotes
    Then I should not see "Project Name"
    When I click view "PO001" in grid
    Then I should not see "Another Project"
    When I continue as the Buyer
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    Then I should not see "Project Name"
    When I click view "PO001" in grid
    Then I should not see "Another Project"
    When I click "Account Dropdown"
    And I click "Quotes"
    Then I should not see "Project Name"
    When I click view "PO001" in grid
    Then I should not see "Another Project"
