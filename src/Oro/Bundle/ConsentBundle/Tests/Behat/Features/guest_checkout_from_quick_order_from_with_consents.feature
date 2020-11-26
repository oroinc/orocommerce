@regression
@feature-BB-16069
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Products_quick_order_form_ce.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Guest Checkout from Quick Order From with Consents
  In order to accept consents on guest checkout
  As an Frontend User
  I want to be able check consents and proceed checkout

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable configuration options:
      | oro_consent.consent_feature_enabled       |
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |
      | oro_product.guest_quick_order_form        |

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

  Scenario: Create Order from Quick order
    Given I proceed as the User
    And I am on the homepage
    And I should see text matching "Quick Order Form"
    And I click "Quick Order Form"
    And I should see "Add to Shopping List"
    And I should see "Create Order"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU1 |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1  | 2   |
    When I click "Create Order"
    And click "Continue as a Guest"
    Then I should not see "Back"
    When I click on "Consent Link" with title "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    And fill form with:
      | I Agree with Email Newsletters | true |
    And click "Continue"
    Then I should see "Back"
    When I fill form with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I should not see "Save address"
    And click "Continue"
    And I fill form with:
      | Label           | Home Address    |
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And "Order Review" checkout step "Order Summary Products Grid" contains products
      | Product1 | 2 | items |
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
