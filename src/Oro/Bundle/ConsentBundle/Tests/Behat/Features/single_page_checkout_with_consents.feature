@feature-BB-15731
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroProductBundle:gdpr_refactor.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Single page checkout with consents
  In order to accept consents on single page checkout
  As an Frontend User
  I want to be able check consents and proceed checkout

  Scenario: Feature Background
    Given I activate "Single Page Checkout" workflow
    And sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable 'oro_consent.consent_feature_enabled' configuration option
    And I enable 'oro_shopping_list.availability_for_guests' configuration option
    And I enable 'oro_checkout.guest_checkout' configuration option

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
    And I save form
    Then I should see "Content Node has been saved" flash message
    And I set "Store and Process" as default web catalog

  Scenario: Admin creates consents (Mandatory without node assigned, mandatory and optional with node)
    Given I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Email Newsletters |
      | Type | Mandatory          |
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
    When I click "Store and Process Node"
    Then save and close form

  Scenario: Admin selects consents to be enabled on Frontstore
    Given go to System/ Configuration
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

  Scenario: Admin sets payment term for Non-Authenticated Visitors group
    Given go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Guest creates shopping list, starts checkout and fills billing address
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    And I click "Add to Shopping List"
    And I open page with shopping list Shopping List
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I uncheck "Save my data and create an account" on the checkout page
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Albania          |
      | State        | Has              |
      | Postal Code  | 12345            |
    And I click "Continue"
    And I check "Use billing address" on the checkout page

  Scenario: Check validation errors while submitting order without accepted consents by Guest
    Given I click "Submit Order"
    Then I should see that "Required Consent" contains "This agreement is required"

  Scenario: Guest accepts consents and submits order
    Given I should not see "Receive notifications"
    And I click on "Consent Link" with title "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    And fill form with:
      | I Agree with Email Newsletters | true |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Logged User creates shopping list and starts checkout
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"

  Scenario: Logged User see validation errors if consents not accepted on order submit
    Given I click "Submit Order"
    Then I should see that "Required Consent" contains "This agreement is required"

  Scenario: Logged User accepts consents and submits order
    Given I should not see "Receive notifications"
    And I click on "Consent Link" with title "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    And fill form with:
      | I Agree with Email Newsletters | true |
    And I reload the page
    And I should see "All mandatory consents were accepted."
    And I should not see "Email Newsletters"
    And I should not see "Collecting and storing personal data"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Logged User should have consents accepted for new order already
    Given I open page with shopping list List 2
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then I should not see "All mandatory consents were accepted."
    And I should not see "Email Newsletters"
    And I should not see "Collecting and storing personal data"

  Scenario: Admin adds one more mandatory consent in website configuration (during customer checkout process is in progress)
    Given I proceed as the Admin
    And I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | New Mandatory Consent |
      | Type | Mandatory             |
    When save and close form
    Then I should see "Consent has been created" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false|
    And click "Add Consent"
    And I choose Consent "New Mandatory Consent" in 4 row
    When click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that redirect was executed and flash message is appeared on checkout
    Given I proceed as the User
    When I click "Submit Order"
    Then I should see "New mandatory consent has been added and requires your attention. Please, review and accept it to proceed."
    And I should see 1 elements "Required Consent"
    And I should see "New Mandatory Consent"
    When fill form with:
      | New Mandatory Consent | true |
    Then I should not see a "Consent Popup" element
    And the "New Mandatory Consent" checkbox should be checked
