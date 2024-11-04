@regression
@ticket-BB-20941
@fixture-OroConsentBundle:Consents.yml
Feature: Consents grid filters
  As an Administrator I want to be able to filter consents by type or declined consent notification

  Scenario: Enable consent functionality
    Given I login as administrator
    And go to System/ Configuration
    And follow "Commerce/Customer/Interactions" on configuration sidebar
    And I uncheck "Use default" for "Enable user consents feature" field
    And I check "Enable user consents feature"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Admin goes to the Consent Management page and uses filters
    Given I go to System/ Consent Management
    Then number of records should be 10
    When I check "Optional" in Type filter
    Then number of records should be 5
    When I check "Mandatory" in Type filter
    Then number of records should be 5
    When I reset Type filter
    Then number of records should be 10
    When I check "Yes" in "Declined Consent Notification" filter
    Then number of records should be 5
    When I check "No" in "Declined Consent Notification" filter
    Then number of records should be 5
