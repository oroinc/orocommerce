@ticket-BB-16837
@fixture-OroTaxBundle:ProductTaxCodePerMultipleOrganization.yml

Feature: Product tax code per multiple organization
  In order to manage product tax code
  As an Administrator
  I want to see the ability to create product codes with the same name in different organization
  I want to create products with taxes that are located only in the current organization

  Scenario: Create product tax code from ORO organization
    Given I login as administrator
    And go to Taxes/ Product Tax Codes
    And there is no records in grid
    When click "Create Product Tax Code"
    And fill form with:
      | Code        | UniqueTaxCode                    |
      | Description | Unique tax code per organization |
    And save and close form
    Then should see "Product Tax Code has been saved" flash message
    When I go to Taxes/ Product Tax Codes
    Then there are one record in grid

  Scenario: Create Product tax code from ORO Pro organization
    Given I am logged in under ORO Pro organization
    And go to Taxes/ Product Tax Codes
    And there is no records in grid
    When click "Create Product Tax Code"
    And fill form with:
      | Code        | UniqueTaxCode                    |
      | Description | Unique tax code per organization |
    And save form
    Then should see "Product Tax Code has been saved" flash message
    When fill form with:
      | Code        | TaxCodePerOrgOROPRO                |
      | Description | Tax code from ORO_PRO organization |
    And save form
    Then should see "Product Tax Code has been saved" flash message

  Scenario: Check tax rule
    Given I go to Taxes/ Tax Rules
    When I click "Create Tax Rule"
    And I fill "Tax Rule Form" with:
      | Customer Tax Code | CustomerTaxCodeFromOroProOrg |
      | Tax Jurisdiction  | TaxJurisdiction              |
      | Tax               | Tax                          |
    And click "Tax Product Code hamburger"
    Then I should see following "Select Product Tax Code Grid" grid:
      | Code                |
      | TaxCodePerOrgOROPRO |
    And should not see "UniqueTaxCode"
    When click on TaxCodePerOrgOROPRO in grid "Select Product Tax Code Grid"
    And I save and close form
    Then I should see "Tax Rule has been saved"

  Scenario: Check lists and popup of taxes only ORO_PRO Organization
    Given I go to Products/ Products
    And I click Edit "SkuOrgOroPro" in grid
    And click "Tax code humburger button"
    And I should see following "SelectTaxCodeGrid" grid:
      | Code                |
      | TaxCodePerOrgOROPRO |
    And should not see "UniqueTaxCode"
    And click on TaxCodePerOrgOROPRO in grid "SelectTaxCodeGrid"
    When save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check taxation in system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/US Sales Tax" on configuration sidebar
    When uncheck "Use default" for "Digital Products Tax Codes" field
    Then I should see the following options for "Digital Products Tax Codes" select in form "Tax US Sales Tax Form":
      | TaxCodePerOrgOROPRO |
    And I should not see the following options for "Digital Products Tax Codes" select in form "Tax US Sales Tax Form":
      | UniqueTaxCode |
    When I fill "Tax US Sales Tax Form" with:
      | Digital Products Tax Codes | TaxCodePerOrgOROPRO |
    And I save form
    Then I should see "Configuration saved" flash message

    And I go to System/Configuration
    And I follow "Commerce/Taxation/EU VAT Tax" on configuration sidebar
    When uncheck "Use default" for "Digital Products Tax Codes" field
    Then I should see the following options for "Digital Products Tax Codes" select in form "Tax EU Vat Tax Form":
      | TaxCodePerOrgOROPRO |
    And I should not see the following options for "Digital Products Tax Codes" select in form "Tax EU Vat Tax Form":
      | UniqueTaxCode |
    When I fill "Tax EU Vat Tax Form" with:
      | Digital Products Tax Codes | TaxCodePerOrgOROPRO |
    And I save form
    Then I should see "Configuration saved" flash message
