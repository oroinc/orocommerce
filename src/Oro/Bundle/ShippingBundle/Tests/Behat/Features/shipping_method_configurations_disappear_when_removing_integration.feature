@regression
@ticket-BB-7664
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroShippingBundle:ShippingMethodsConfigsRule.yml
Feature: Shipping Method Configurations disappear when removing integration
  As an Administrator
  I want to be sure that Shipping Method Configurations is disappear when removing integration
  So I disable Shipping Integration

  #If integration will be disabled, then during saving we need to inform (show pop-up) admin about existing shipping rules which will be disabled/modified after this integration will be disabled. We also have to provide link to the grid with list of such rules in this pop-up.
  #If administrator wants to disable integration temporary, and if shipping rule contain other enabled methods. After integration will be disabled, we have to change shipping rule and mark disabled methods with label “disabled”. If shipping rule contains only one method which was disabled, then we have to disable this rule.
  #If admin user is going to delete integration, we also should show pop-up and remove method from rule (if there is other methods in rule) or remove rule (if there is no other methods in rule).

  #Preconditions:
  # default Flat Rate integration and shipping rule with only one method from this default integration.

  Scenario: Disabling Shipping Integration And
    Given I login as administrator
    And I go to System/ Integrations/ Manage Integrations
    And I click "Create Integration"
    And I fill "Integration Form" with:
      | Type | Flat Rate Shipping |
    And I fill "Integration Form" with:
      | Name | New Flat Rate      |
      | Label| Flat Rate New      |
    And I save and close form
    And I go to System/ Shipping Rules
    And I click "Create Shipping Rule"
    And I fill "Shipping Rule" with:
      |Enabled   |true             |
      |Name      |New Shipping Rule|
      |Sort Order|1                |
      |Currency  |$                |
      |Method    |Flat Rate New    |
    And I fill "Shipping Rule" with:
      |Price     |25               |
    And I save and close form
    And I go to System/ Integrations/ Manage Integrations
    And I click edit New Flat Rate in grid
    When I click "Deactivate"
    Then I should see Existing Shipping Rules popup
    And I click on "Deactivate Integration Confirm Button"
    And I go to System/ Shipping Rules
    And I should see New Shipping Rule in grid with following data:
      |Configurations|Flat Rate New (Price: $25.00) Disabled|
    And I click edit New Shipping Rule in grid
    And I should see Disabled Shipping Method Configuration
    And I click Logout in user menu
