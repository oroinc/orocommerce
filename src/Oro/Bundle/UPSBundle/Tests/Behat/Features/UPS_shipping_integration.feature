Feature: UPS shipping integration
  Scenario: Create UPS integration
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    When I fill "Integration Form" with:
      | Type                    | UPS                       |
      | Name                    | UPS                       |
      | Label                   | UPS                       |
      | Test Mode               | true                      |
      | API User                | api_user                  |
      | API Password            | api_password              |
      | API Key                 | api_key                   |
      | Shipping Account Name   | Oro Inc.                  |
      | Shipping Account Number | 123                       |
      | Country                 | United States             |
      | Shipping Services       | [UPS Ground, UPS Express] |
    And save and close form
    Then I should see "Integration saved" flash message
    And I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true |
      | Name       | UPS  |
      | Sort Order | 1    |
      | Currency   | $    |
      | Method     | UPS  |
    And fill "UPS Shipping Rule Form" with:
      | UPS Ground Enable    | true |
      | UPS Ground Surcharge | 10   |
    When save and close form
    Then should see "Shipping rule has been saved" flash message
