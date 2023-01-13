@ticket-BB-21891

Feature: Check if the price attribute can be removed if using fixed product integration

  Scenario: Create "Fixed Product Shipping Cost" integration
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    When I click "Create Integration"
    And fill "Fixed Product Shipping Cost Form" with:
      | Type          | Fixed Product Shipping Cost |
      | Name          | Fixed Product Shipping Cost |
      | Label         | Fixed Product Shipping Cost |
      | Status        | Active                      |
      | Default owner | John Doe                    |
    When I save form
    Then I should see "Integration saved" flash message

  Scenario: Create shipping rule
    Given I go to System/ Shipping Rules
    When I click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Name       | Fixed Product               |
      | Sort Order | 1                           |
      | Currency   | USD                         |
      | Method     | Fixed Product Shipping Cost |
    And fill "Fast Shipping Rule Form" with:
      | Surcharge Type   | Percent       |
      | Surcharge On     | Product Price |
      | Surcharge Amount | 10            |
    And save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Remove "Shipping Cost" price attribute
    Given I go to Products/ Price Attributes
    When I click delete "Shipping Cost" in grid
    And confirm deletion
    Then I should see "This attribute cannot be deleted as it is used by 'Fixed Product Shipping Cost' integration." flash message

  Scenario: Disable "Fixed Product Shipping Cost" integration
    Given I go to System/ Integrations/ Manage Integrations
    When I click deactivate "Fixed Product Shipping Cost" in grid
    Then should see "Integration has been deactivated successfully" flash message

  Scenario: Remove "Shipping Cost" price attribute
    Given I go to Products/ Price Attributes
    When I click delete "Shipping Cost" in grid
    And confirm deletion
    Then I should see "This attribute cannot be deleted as it is used by 'Fixed Product Shipping Cost' integration." flash message

  Scenario: Remove "Fixed Product" shipping rule
    Given I go to System/ Shipping Rules
    When I click delete "Fixed Product" in grid
    And confirm deletion
    Then I should see "Shipping Rule deleted" flash message

  Scenario: Remove "Shipping Cost" price attribute
    Given I go to Products/ Price Attributes
    When I click delete "Shipping Cost" in grid
    And confirm deletion
    Then I should see "Price Attribute deleted" flash message
