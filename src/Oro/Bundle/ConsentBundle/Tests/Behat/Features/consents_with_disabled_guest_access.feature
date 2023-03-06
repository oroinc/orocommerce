@regression
@fix-BB-14887
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
Feature: Consents with disabled Guest Access
  In order to accept consents on registration page
  As an Guest Customer User
  I want to be able check consents and proceed registration

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable configuration options:
      | oro_consent.consent_feature_enabled |

  Scenario: Disable guest access
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    When I save form
    Then I should see "Configuration Saved" flash message

  Scenario: Admin creates Landing Page and Content Node in Web Catalog
    Given go to Marketing/ Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Store and Process |
    When I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    And I click "Edit Content Tree"
    And I fill "Content Node Form" with:
      | Titles | Home page |
    And I click "Add System Page"
    When I save form
    Then I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Store and Process Node |
      | Url Slug     | store-and-process-node |
      | Landing Page | Consent Landing        |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Test Node     |
      | Url Slug     | test-node     |
      | Landing Page | Test CMS Page |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I set "Store and Process" as default web catalog

  Scenario: Admin creates consents (Mandatory without node assigned, mandatory and optional with node)
    Given I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Email Newsletters |
      | Type | Mandatory         |
    And I save and create new form
    And fill "Consent Form" with:
      | Name        | Collecting and storing personal data |
      | Type        | Mandatory                            |
      | Web Catalog | Store and Process                    |
    And I click "Store and Process Node"
    And I save and create new form
    And fill "Consent Form" with:
      | Name        | Receive notifications |
      | Type        | Optional              |
      | Web Catalog | Store and Process     |
    And I click "Store and Process Node"
    And save and close form

  Scenario: Admin selects consents to be enabled on Storefront
    Given I go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false|
    And click "Add Consent"
    And I choose Consent "Email Newsletters" in 1 row
    And click "Add Consent"
    And I choose Consent "Collecting and storing personal data" in 2 row
    And click "Add Consent"
    And I choose Consent "Receive notifications" in 3 row
    When click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Guest create an Account
    Given I proceed as the User
    And I am on homepage
    When I click "Register"
    Then I should see 2 elements "Required Consent"
    And I should see 1 elements "Optional Consent"
    And I fill form with:
      | Company Name                   | OroCommerce               |
      | First Name                     | Amanda                    |
      | Last Name                      | Cole                      |
      | Email Address                  | AmandaRCole1@example.org  |
      | Password                       | AmandaRCole1@example.org  |
      | Confirm Password               | AmandaRCole1@example.org  |
      | I Agree with Email Newsletters | true                      |
    When I click "Receive notifications"
    Then I should see "UiDialog" with elements:
      | Title             | Receive notifications |
      | Disabled okButton | Agree                 |
      | cancelButton      | Cancel                |
    And I scroll modal window to bottom
    And I click "Agree"
    When I click "Collecting and storing personal data"
    Then I should see "UiDialog" with elements:
      | Title             | Collecting and storing personal data |
      | Disabled okButton | Agree                                |
      | cancelButton      | Cancel                               |
    And I scroll modal window to bottom
    And click "Agree"
    When click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message
