@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
Feature: Check validation for Shipping Rule - Flat rate
  In order to validate flat rate shipping rule options
  As Administrator
  I need to be able to add/change flat rate shipping rule

  Scenario: Get validation error on saving shipping method
    Given I login as administrator
    And go to System/ Shipping Rules
    And I click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Name       | test rule |
      | Sort Order | 1         |
      | Currency   | USD       |
      | Method     | Flat Rate |
    And fill "Fast Shipping Rule Form" with:
      | Handling Fee | 10       |
      | Type         | per_item |
    And I save and close form
    Then I should see "Shipping Rule Flat Rate" validation errors:
      | Price | This value should not be blank. |

  Scenario: Save shipping method successfully
    And I fill "Shipping Rule Flat Rate" with:
      | Price | 15 |
    And I save and close form
    Then I should see "Shipping rule has been saved" flash message
