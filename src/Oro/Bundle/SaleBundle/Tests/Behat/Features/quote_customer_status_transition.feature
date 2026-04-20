@ticket-BAP-22796
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml

Feature: Quote Customer Status Transition
  In order to update quote customer status using custom workflow
  As an Administrator
  I want to be able to create workflow with enum field attribute and use it for changing quote customer status

  Scenario: Create workflow for changing quote customer status
    Given I login as administrator
    Then I go to System/ Workflows

    And I click "Create Workflow"
    And I fill form with:
      | Name            | Change Quote Status |
      | Related Entity | Quote                |

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Change Customer Status |
      | Position | 0                  |
    And I click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name      | Customer Status        |
      | From step | (Start)                 |
      | To step   | Change Customer Status |
      | View Form | Popup window           |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | Customer Status |
      | Required     | true           |
    And click "Add"
    And click "Apply"

    Then save and close form
    And click "Activate"
    And I click "Activate" in modal window
    And I should see "Workflow activated" flash message

  Scenario: Use workflow to change quote customer status
    Given go to Sales/Quotes
    And I should see following grid containing rows:
      | Customer Status | PO Number |
      |                  | PO1       |

    Then click view PO1 in grid
    And I should see "Customer Status N/A"
    And I should see "Customer Status" button

    Then I click "Customer Status"
    And I select "Accepted" from "Customer Status"
    And I click "Submit"
    And I should not see "Customer Status N/A"
    And I should see "Customer Status Accepted"
    And I should not see "Customer Status" button

    Then I go to Sales/Quotes
    Then I should see following grid containing rows:
      | Customer Status | PO Number |
      | Accepted         | PO1       |
