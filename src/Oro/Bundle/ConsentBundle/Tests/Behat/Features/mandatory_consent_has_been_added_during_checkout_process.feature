@regression
@ticket-BB-17191
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroProductBundle:gdpr_refactor.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Mandatory Consent has been added during Checkout process
  In order to accept consents on checkout page
  As a Customer User
  I want to be able check consents and proceed checkout

  Scenario: Create two sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Admin creates Landing Page and Content Node in Web Catalog
    Given I proceed as the Admin
    When I login as administrator
    And go to Marketing/ Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Store and Process |
    And I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    When I click "Edit Content Tree"
    And I fill "Content Node Form" with:
      | Titles | Home page |
    And I click "Add System Page"
    And I save form
    Then I click "Create Content Node"
    When I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Store and Process Node |
      | Url Slug     | store-and-process-node |
      | Landing Page | Consent Landing        |
    And I save form
    Then I should see "Content Node has been saved" flash message
    And I set "Store and Process" as default web catalog

  Scenario: Enable consent functionality via feature toggle
    When go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    And click "Save settings"
    Then I should see a "Sortable Consent List" element

  Scenario: Admin creates consents
    When I go to System/ Consent Management
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
    Then save and close form

  Scenario: Admin selects consents to be enabled on Storefront
    When I go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false|
    And click "Add Consent"
    And I choose Consent "Email Newsletters" in 1 row
    And click "Add Consent"
    And I choose Consent "Collecting and storing personal data" in 2 row
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check mandatory consents on Checkout Page
    Given I proceed as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And click "Quick Order Form"
    And fill "Quick Order Form" with:
      | SKU1 | Lenovo_Vibe1_sku |
    And I wait for products to load
    And fill "Quick Order Form" with:
      | QTY1 | 10 |
    And click "Create Order"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see 2 elements "Required Consent"
    And the "Email Newsletters" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    When I check "Email Newsletters"
    Then the "Email Newsletters" checkbox should be checked
    When I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    Then the "Collecting and storing personal data" checkbox should be checked
    When click "Continue"
    Then I should see "Billing Information" in the "Checkout Step Title" element

  Scenario: Decline consent from My profile page
    When I set alias "checkout" for the current browser tab
    And I open a new browser tab and set "profile" alias for it
    And follow "Account"
    And I click "Edit Profile Button"
    Then the "Collecting and storing personal data" checkbox should be checked
    And the "Email Newsletters" checkbox should be checked
    When fill form with:
      | Email Newsletters | false |
    And I save form
    And click "Yes, Decline"
    Then should see "Customer User profile updated" flash message
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element

  Scenario: Check that redirect was executed and flash message is appeared on checkout
    When I switch to the browser tab "checkout"
    And I click "Continue"
    Then I should see "You have been redirected to the Agreements page as a new mandatory consent has been added and requires your attention. Please, review and accept it to proceed." flash message and I close it
    And I should see "Agreements" in the "Checkout Step Title" element
    And I should see 1 elements "Required Consent"
    And I should see "Email Newsletters"

  Scenario: Finish checkout
    When I check "Email Newsletters"
    And I click "Continue"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I follow "Account"
    Then I should see "Accepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
