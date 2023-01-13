@fix-BB-20612
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml

Feature: Consent may be declined by customer user
  In order to work with consents
  As a Customer User
  I want to be able accept and decline consents at my account management

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_consent.consent_feature_enabled |

  Scenario: Admin creates Landing Page and Content Node in Web Catalog
    Given I proceed as the Admin
    And I login as administrator
    When go to Marketing/ Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Store and Process |
    And I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    And I set "Store and Process" as default web catalog

    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Store and Process" in grid
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Home page                               |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Foo Node      |
      | Url Slug     | foo-node      |
      | Landing Page | Test CMS Page |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Admin creates consent
    Given I go to System/ Consent Management
    When click "Create Consent"
    And fill "Consent Form" with:
      | Name        | Collecting and storing personal data |
      | Type        | Mandatory                            |
      | Web Catalog | Store and Process                    |
    And I click "Foo Node"
    And save and close form
    Then I should see "Consent has been created" flash message

  Scenario: Add Multiple Files field to Contact Request entity
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "ContactRequest"
    And click View ContactRequest in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | custom_multiple_files |
      | Storage Type | Table column          |
      | Type         | Multiple Files        |
    And I click "Continue"
    And I fill form with:
      | Label          | Custom Multiple Files |
      | File Size (MB) | 10                    |
    And I save and close form
    And I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Admin selects consents to be enabled on Frontstore
    Given go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false |
    And click "Add Consent"
    And I choose Consent "Collecting and storing personal data" in 1 row
    Then click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Manage consents from My profile page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When follow "Account"
    Then should see a "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
    When I click "Edit Profile Button"
    Then the "Collecting and storing personal data" checkbox should not be checked
    When fill form with:
      | Collecting and storing personal data | true |
    And I scroll modal window to bottom
    And click "Agree"
    Then the "Collecting and storing personal data" checkbox should be checked
    When I save form
    Then should see "Customer User profile updated" flash message
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element

    When I click "Edit Profile Button"
    And fill form with:
      | Collecting and storing personal data | false |
    And I save form
    And click "Yes, Decline"
    Then should see "Customer User profile updated" flash message
    And I should see "Unaccepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
