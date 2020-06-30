@regression
@ticket-BB-19210
@fixture-OroApplicationBundle:NewFieldsInViewAndEditPage.yml

Feature: New fields appear on the view and edit pages
  In order to be able to correctly work with extend fields
  As an administrator
  I add new extend fields to entities and check they can be modified on edit pages and displayed on view pages

  Scenario: Feature Background
    Given I login as administrator
    # Enable Consent Management
    And go to System/Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    And click "Save settings"

  Scenario Outline: Create extend fields
    Given I go to System/Entities/Entity Management
    And I filter "Name" as is equal to "<Entity name>"
    And I click "view" on row "<Entity name>" in grid
    When I click "Create Field"
    And I fill form with:
      | Field Name   | NewField     |
      | Storage Type | Table column |
      | Type         | String       |
    And click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Entity name                |
      | WebCatalog                 |
      | PaymentMethodsConfigsRule  |
      | LoginPage                  |
      | Page                       |
      | ContentBlock               |
      | PaymentTerm                |
      | Consent                    |
      | Promotion                  |
      | ShippingMethodsConfigsRule |
      | ContactReason              |
      | EmailTemplate              |
      | Role                       |
      | EmbeddedForm               |
      | Tag                        |
      | Taxonomy                   |

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario Outline: Check a new field has appeared on view and edit pages
    Given I go to <Path>
    When I click edit <Name> in grid
    And fill form with:
      | NewField | NewFieldValue |
    And I click "Save and Close"
    Then I should see "NewField NewFieldValue"

    Examples:
      | Path                               | Name            |
      | System/Integrations/Embedded Forms | Embedded form   |
      | System/Tags Management/Taxonomies  | Taxonomy        |
      | System/User Management/Roles       | Account Manager |
      | System/Consent Management          | Consent         |
      | System/Shipping Rules              | Shipping Rule   |
      | System/Payment Rules               | Payment Rule    |
      | Marketing / Customer Login Pages   | 1               |
      | Marketing/Promotions/Promotions    | Promotion rule  |
      | Marketing/Content Blocks           | Content block   |
      | Marketing/Landing Pages            | Landing Page    |
      | Marketing/Web Catalogs             | Web Catalog     |
      | Sales/Payment terms                | Payment term    |

  Scenario Outline: Check a new field has appeared on edit page
    Given I go to <Path>
    And filter <Filter> as is equal to "<Name>"
    When I click edit <Name> in grid
    And fill form with:
      | NewField | NewFieldValue |
    When I click "Save and Close"
    And filter <Filter> as is equal to "<Name>"
    Then I should see following grid:
      | NewField      |
      | NewFieldValue |

    Examples:
      | Path                        | Name           | Filter        |
      | System/Tags Management/Tags | Tag            | Name          |
      | System/Emails/Templates     | Email template | Template name |
      | System/Contact Reasons      | Contact reason | Label         |
