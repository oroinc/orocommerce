@ticket-BAP-18091
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroApplicationBundle:DisplayInGridEntities.yml
@regression

Feature: New Field Display in Grid (Commerce)
  In order to make sure that main entity grids respect "Display in Grid" setting
  As an Administrator
  I want to add a new field to a configurable entity, mark "Display in Grid" and check that field is appears on grid

  Scenario: Login to Admin Panel
    Given I login as administrator
    And I enable configuration options:
      | oro_consent.consent_feature_enabled |

  Scenario Outline: Add new field and mark as Display in Grid
    Given I go to System/Entities/Entity Management
    And I filter Name as Is Equal To "<Name>"
    And I check "<Module>" in Module filter
    And I click View <Name> in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | <Field>      |
      | Storage Type | Table column |
      | Type         | String       |
    And I click "Continue"
    And I save and close form
    Examples:
      | Name                       | Field     | Module                 |
      | WebCatalog                 | TestField | OroWebCatalogBundle    |
      | Brand                      | TestField | OroProductBundle       |
      | Product                    | TestField | OroProductBundle       |
      | PaymentMethodsConfigsRule  | TestField | OroPaymentBundle       |
      | LoginPage                  | TestField | OroCMSBundle           |
      | ContentBlock               | TestField | OroCMSBundle           |
      | Page                       | TestField | OroCMSBundle           |
      | PaymentTerm                | TestField | OroPaymentTermBundle   |
      | PriceList                  | TestField | OroPricingBundle       |
      | Request                    | TestField | OroRFPBundle           |
      | Order                      | TestField | OroOrderBundle         |
      | Consent                    | TestField | OroConsentBundle       |
      | Quote                      | TestField | OroSaleBundle          |
      | ShoppingList               | TestField | OroShoppingListBundle  |
      | Promotion                  | TestField | OroPromotionBundle     |
      | ShippingMethodsConfigsRule | TestField | OroShippingBundle      |

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario Outline: Check new field in grid settings
    Given I go to System/Entities/Entity Management
    And I filter Name as Is Equal To "<Name>"
    And I check "<Module>" in Module filter
    And I click View <Name> in grid
    And I click "Number of records"
    When click "Grid Settings"
    Then I should see following columns in the grid settings:
      | <Field> |
    Examples:
      | Name                       | Field     | Module                 |
      | WebCatalog                 | TestField | OroWebCatalogBundle    |
      | Brand                      | TestField | OroProductBundle       |
      | Product                    | TestField | OroProductBundle       |
      | PaymentMethodsConfigsRule  | TestField | OroPaymentBundle       |
      | LoginPage                  | TestField | OroCMSBundle           |
      | ContentBlock               | TestField | OroCMSBundle           |
      | Page                       | TestField | OroCMSBundle           |
      | PaymentTerm                | TestField | OroPaymentTermBundle   |
      | PriceList                  | TestField | OroPricingBundle       |
      | Request                    | TestField | OroRFPBundle           |
      | Order                      | TestField | OroOrderBundle         |
      | Consent                    | TestField | OroConsentBundle       |
      | Quote                      | TestField | OroSaleBundle          |
      | ShoppingList               | TestField | OroShoppingListBundle  |
      | Promotion                  | TestField | OroPromotionBundle     |
      | ShippingMethodsConfigsRule | TestField | OroShippingBundle      |
