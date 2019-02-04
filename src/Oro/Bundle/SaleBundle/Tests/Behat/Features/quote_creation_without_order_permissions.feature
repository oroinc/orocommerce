@regression
@ticket-BB-15928
@fixture-OroPricingBundle:FractionalProductPrices.yml

Feature: Quote creation without Order permissions
  In order to be able to create quotes from back office
  As an Administrator
  I want Quote creation to not require any Order permissions

  Scenario: Set Order permissions to None
    Given I login as administrator
    And I go to System/ User Management/ Roles
    And I click edit Administrator in grid
    And select following permissions:
      | Order | View:None | Create:None | Edit:None | Delete:None |
    When I save and close form
    Then I should see "Role Saved" flash message

  Scenario: Create and view Quote with fractional price
    When I go to Sales/Quotes
    And I click "Create Quote"
    When I fill "Quote Form" with:
      | Customer        | first customer |
      | LineItemProduct | psku1          |
    Then I should not see "You do not have permission to perform this action" flash message

    When I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Quote has been saved" flash message
