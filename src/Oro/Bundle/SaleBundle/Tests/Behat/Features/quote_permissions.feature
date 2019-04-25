@ticket-BB-16519
@fixture-OroSaleBundle:Quote.yml

Feature: Quote permissions
  In order to restrict quote permissions
  As an Administrator
  I should be able to set needed restrictions for the role and see changes on the quote view page

  Scenario: Check "Expire Quote" button available by default
    Given I login as administrator
    And I go to System/Workflows
    And I click "Deactivate" on row "Backoffice Quote Flow with Approvals" in grid
    When click "Yes, Deactivate"
    Then should see "Workflow deactivated" flash message
    And I go to Sales/Quotes
    When I click view Quote1 in grid
    Then I should see "Expire Quote"

  Scenario: Check "Expire Quote" is not shown when edit quote permissions disabled
    Given I go to System/ User Management/ Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Quote | Edit:None |
    When I save form
    Then I should see "Role saved" flash message
    And I go to Sales/Quotes
    When I click view Quote1 in grid
    Then I should not see "Expire Quote"
