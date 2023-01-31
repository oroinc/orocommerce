@regression
@ticket-BB-19383
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroCustomerBundle:ShoppingListFixture.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
Feature: Consent are stored correctly even if consent content node is not a part of the navigation catalog
  As an Administrator I want to be able to change consent landing page even
  if the consent content node is not a part of the navigation catalog.

  As a Customer User I want to sign an actual consent in case if landing page was changed by the Administrator
  even if the customer user from the same Scope previously sign the same consent with the previous landing page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable configuration options:
      | oro_consent.consent_feature_enabled       |

  Scenario: Admin creates Landing Page and Content Node in Web Catalog
    Given I proceed as the Admin
    And I login as administrator
    And go to Marketing/ Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Store and Process |
    When I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    And I click "Edit Content Tree"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Home page                               |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Foo Node     |
      | Url Slug     | foo-node     |
      | Landing Page | Test CMS Page |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Home page"
    Then I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Bar Node |
      | Url Slug     | bar-node |
      | Landing Page | Consent Landing        |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I set "Store and Process" as default web catalog

  Scenario: Admin creates consents (Mandatory without node assigned, mandatory and optional with node)
    Given I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name        | Collecting and storing personal data |
      | Type        | Mandatory                            |
      | Web Catalog | Store and Process                    |
    Then I click "Foo Node"
    Then save and close form

  Scenario: Admin selects consents to be enabled on Frontstore
    Given go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false|
    And click "Add Consent"
    And I choose Consent "Collecting and storing personal data" in 1 row
    Then click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Admin set navigation catalog root to the not a consent node
    Given go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Navigation Root" field
    And I click "Bar Node"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Logged User creates shopping list and go to rfq and check consent page
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list ShoppingList1
    And I scroll to top
    And I wait line items are initialized
    And I click "More Actions"
    And I click "Request Quote"
    And I click on "Consent Link" with title "Collecting and storing personal data"
    And I should see "Test landing page description"
    And I scroll modal window to bottom
    And click "Agree"
    Then I click "Submit Request"

  Scenario: Admin check signed consent for the customer user
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers / Customer Users
    Then I click on AmandaRCole@example.org in grid
    And I click on "Customer user View page Consent Link"
    And I should see "Test landing page description"
    And I should not see "Lorem ipsum dolor sit amet, consectetur adipiscing elit."

  Scenario:  Admin change actual consent page to a different one
    Given I proceed as the Admin
    And I login as administrator
    And go to Marketing/ Web Catalogs
    Then I click on Store and Process in grid
    And I click "Edit Content Tree"
    Then I click "Foo Node"
    And I click on "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Landing Page | Consent Landing |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Logged User creates shopping list and go to rfq and sign changed consent page
    Given I signed in as MarleneSBradley@example.com on the store frontend
    And I open page with shopping list ShoppingList2
    And I scroll to top
    And I wait line items are initialized
    And I click "More Actions"
    And I click "Request Quote"
    And I click on "Consent Link" with title "Collecting and storing personal data"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
    And I should not see "Test landing page description"
    And I scroll modal window to bottom
    And click "Agree"
    Then I click "Submit Request"

  Scenario: Admin check signed consent for the customer user
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers / Customer Users
    Then I click on MarleneSBradley@example.com in grid
    And I click on "Customer user View page Consent Link"
    And I should not see "Test landing page description"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
