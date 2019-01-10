@feature-BB-13768
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroProductBundle:gdpr_refactor.yml
@fixture-OroConsentBundle:ConsentLandingPagesFixture.yml
Feature: Consent management via Management Console UI
  In order to be able to manage consents in OroCommerce
  As an Administrator
  I want to provide the ability to CRUD consents in the system

  Scenario: Create two sessions
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create Landing Page and Content Node in Web Catalog
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
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Enable consent functionality via feature toggle
    Given go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see a "Sortable Consent List" element
    And fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    When click "Save settings"
    Then I should see a "Sortable Consent List" element

  Scenario: Admin User is able to CRUD consents
    Given I go to System/ Consent Management
    And click "Create Consent"
    When click "Save and Close"
    Then should see "Consent Form" validation errors:
      | Name | This value should not be blank. |
    And fill "Consent Form" with:
      | Name | Presenting Personal Data |
      | Type | Optional                 |
    When I save and close form
    Then should see "Consent has been created" flash message
    And I click "Edit"
    And fill "Consent Form" with:
      | Type | Mandatory |
    When I click "Web Catalog Hamburger Button"
    Then I should see following grid:
      | Id | Name              |
      | 1  | Store and Process |
    When close ui dialog
    Then I should see "Please choose a Web Catalog"
    And I fill form with:
      | Web Catalog | Store and Process |
    And I should not see "Please choose a Web Catalog"
    And I click "Store and Process Node"
    And save and close form
    And should see "Consent has been saved" flash message
    When I click "Delete"
    Then I should see "Are you sure you want to delete this consent?"
    And I click "Cancel"
    When go to System/ Consent Management
    Then I should see following grid:
      | Name                     | Type      | Content Node           | Content Source  |
      | Presenting Personal Data | Mandatory | Store and Process Node | Consent Landing |
    And I set "Store and Process" as default web catalog
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Email Newsletters |
      | Type | Optional          |
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
    And save and close form
    When go to System/ Consent Management
    Then I should see following grid:
      | Name                                 | Type      | Content Node           | Content Source  |
      | Receive notifications                | Optional  | N/A                    | N/A             |
      | Collecting and storing personal data | Mandatory | Store and Process Node | Consent Landing |
      | Email Newsletters                    | Optional  | N/A                    | N/A             |
      | Presenting Personal Data             | Mandatory | Store and Process Node | Consent Landing |

  Scenario: Admin User is able to enable/disable consents functionality on System/Website level
    Given go to System/ Configuration
    When follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false |
    When click "Save settings"
    Then I should see "Configuration saved" flash message
    And click "Add Consent"
    And click "Add Consent"
    And click "Add Consent"
    And click "Add Consent"
    And I choose Consent "Presenting Personal Data" in 1 row
    And I choose Consent "Email Newsletters" in 2 row
    And I choose Consent "Collecting and storing personal data" in 3 row
    And I choose Consent "Receive notifications" in 4 row
    And I drag 2 row to the top in "Consent" table
    When click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row

  Scenario: Show consents on registration page
    Given I proceed as the User
    And I am on the homepage
    When click "Register"
    Then I should see 2 elements "Required Consent"
    And I should see 2 elements "Optional Consent"
    And I should not see "Consent Link" in the "Optional Consent" element
    And the "Presenting Personal Data" checkbox should not be checked
    And the "Email Newsletters" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    And the "Receive notifications" checkbox should not be checked
    And I fill form with:
      | Company Name                         | OroCommerce               |
      | First Name                           | Amanda                    |
      | Last Name                            | Cole                      |
      | Email Address                        | AmandaRCole1@example.org  |
      | Password                             | AmandaRCole1@example.org  |
      | Confirm Password                     | AmandaRCole1@example.org  |
    When click "Create An Account"
    Then I should see that "Required Consent" contains "This agreement is required"
    And I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title             | Presenting Personal Data |
      | Disabled okButton | Agree                    |
      | cancelButton      | Cancel                   |
    When I scroll modal window to bottom
    Then I should see "UiDialog" with elements:
      | Title        | Presenting Personal Data |
      | okButton     | Agree                    |
      | cancelButton | Cancel                   |
    When I click "Agree"
    Then I should not see a "Consent Popup" element
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    And the "Presenting Personal Data" checkbox should be checked
    And the "Email Newsletters" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should be checked
    When click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message

  Scenario: Confirmation of registration a new user who has accepted consents
    Given I proceed as the Admin
    And go to Customers/Customer Users
    And click view "AmandaRCole1@example.org" in grid
    And click "Confirm"
    And I should see "Confirmation successful" flash message
    And I should see customer with:
      | Receive notifications                    | No         |
      | Collecting and storing personal data     | Yes        |
      | Email Newsletters                        | No         |
      | Presenting Personal Data                 | Yes        |
    And click "Edit"
    And fill form with:
      |Buyer (Predefined)        |false|
      |Administrator (Predefined)|true |
    And save and close form

  Scenario: Manage consents from My profile page
    Given I proceed as the User
    And I signed in as AmandaRCole1@example.org on the store frontend
    When click "Account"
    Then should see a "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
    And I should not see "Consent Item Link" in the "Unaccepted Consent" element
    When I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title        | Presenting Personal Data         |
      | cancelButton | Close                            |
    When click "Close"
    Then I should not see a "Consent Popup" element
    When I click "Edit Profile Button"
    Then the "Email Newsletters" checkbox should not be checked
    And the "Presenting Personal Data" checkbox should be checked
    And the "Collecting and storing personal data" checkbox should be checked
    And I should not see "Consent Link" in the "Optional Consent" element
    And fill form with:
      | Presenting Personal Data             | false |
      | Collecting and storing personal data | false |
      | Email Newsletters                    | true  |
    When I save form
    Then I should see "UiWindow" with elements:
      | Title        | Data Protection                                                 |
      | Content      | Are you sure you want to decline the consents accepted earlier? |
      | okButton     | Yes, Decline                                                    |
      | cancelButton | No, Cancel                                                      |
    And I click "No, Cancel"
    When I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title             | Presenting Personal Data |
      | Disabled okButton | Agree                    |
      | cancelButton      | Cancel                   |
    When click "Cancel"
    Then the "Presenting Personal Data" checkbox should not be checked
    And I click "Presenting Personal Data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Presenting Personal Data" checkbox should be checked
    And the "Email Newsletters" checkbox should be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    And I save form
    And click "Yes, Decline"
    Then should see "Customer User profile updated" flash message
    When click "Account"
    Then I should see "Accepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element

  Scenario: Check consents section after changing customer user role
    Given I proceed as the User
    And click "Account"
    And click "Users"
    And click edit AmandaRCole1@example.org in grid
    And I fill form with:
      | Administrator | False |
      | Buyer         | True  |
    When I click "Save"
    Then I should see "Customer User has been saved"
    And I proceed as the Admin
    And go to Customers/Customer Users
    And click view "AmandaRCole@example.org" in grid
    And I should see customer with:
      | Receive notifications                    | No         |
      | Collecting and storing personal data     | No         |
      | Email Newsletters                        | Yes        |
      | Presenting Personal Data                 | Yes        |

  Scenario: Check mandatory consents before creating an RFQ
    Given I proceed as the User
    When click "Requests For Quote"
    Then click "New Quote"
    And I should see 1 elements "Required Consent"
    And I should not see an "Optional Consent" element
    And I should not see "Presenting Personal Data"
    And I should not see "Email Newsletters"
    And the "Collecting and storing personal data" checkbox should not be checked
    And I fill form with:
      | First Name    | Amanda                                                                |
      | Last Name     | Cole                                                                  |
      | Email Address | AmandaRCole1@example.org                                              |
      | Company       | Oro Inc                                                               |
      | Notes         | Testing the way required consents are displayed before submitting RFQ |
    When click "Submit Request"
    Then I should see that "Required Consent" contains "This agreement is required"
    When I click "Collecting and storing personal data"
    Then I should see "UiDialog" with elements:
      | Title             | Collecting and storing personal data |
      | Disabled okButton | Agree                                |
      | cancelButton      | Cancel                               |
    And click "Cancel"
    And the "Collecting and storing personal data" checkbox should not be checked
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Collecting and storing personal data" checkbox should be checked
    When click "Submit Request"
    Then should see "Request has been saved" flash message

  Scenario: When deleting consent, it should be removed from system config
    Given I proceed as the Admin
    And go to System/ Websites
    When click "Configuration" on row "Default" in grid
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And uncheck "Use System" for "Enabled user consents" field
    When submit form
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row
    And I should see that "Receive notifications" is in 4 row
    And I go to System/ Consent Management
    When click delete "Receive notifications" in grid
    Then I should see "Are you sure you want to delete this consent?"
    When I click "Yes, Delete"
    Then I should not see "Receive notifications"
    And go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see "Receive notifications"
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    When follow "Commerce/Customer/Consents" on configuration sidebar
    Then I should not see "Receive notifications"
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row

  Scenario: Accepted consents can't be deleted or edited
    Given I go to System/ Consent Management
    Then I should not see following actions for Collecting and storing personal data in grid:
      | Edit   |
      | Delete |
    And I should not see following actions for Presenting Personal Data in grid:
      | Edit   |
      | Delete |
    And I should not see following actions for Email Newsletters in grid:
      | Edit   |
      | Delete |

  Scenario: Admin User is unable to edit/delete CMS page, which has relation to applied consent
    Given I go to Marketing/ Landing Pages
    Then I should see following actions for About in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should see following actions for Consent Landing in grid:
      | View |
    And I should not see following actions for Consent Landing in grid:
      | Edit   |
      | Delete |

  Scenario: Accepted consents can be deleted from system config
    Given go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And I remove "Presenting Personal Data" from Consent
    When click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Collecting and storing personal data" is in 2 row

  Scenario: When User submits the registration form with any removed consent or CMS page, there should be a validation error
    Given I proceed as the Admin
    And I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name        | Test Consent      |
      | Type        | Mandatory         |
      | Web Catalog | Store and Process |
    And click on "Expand Store and Process Node"
    And click "Test Node"
    When save and close form
    Then I should see "Consent has been created" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And click "Add Consent"
    And I choose Consent "Test Consent" in 4 row
    And click "Save settings"
    # Proceeding to the registration form
    And I proceed as the User
    And I click "Sign Out"
    And I am on the homepage
    When click "Register"
    Then I should see 3 elements "Required Consent"
    And I fill form with:
      | Company Name                         | OroCommerce                 |
      | First Name                           | Branda                      |
      | Last Name                            | Sanborn                     |
      | Email Address                        | BrandaJSanborn1@example.org |
      | Password                             | BrandaJSanborn1@example.org |
      | Confirm Password                     | BrandaJSanborn1@example.org |
    And I click "Presenting Personal Data"
    And I scroll modal window to bottom
    And click "Agree"
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    When I click "Test Consent"
    Then I should see a "Consent Popup" element
    And click "Agree"
    # Proceeding to management console
    And I proceed as the Admin
    And go to System/ Consent Management
    When I click delete "Test Consent" in grid
    Then I should see "Are you sure you want to delete this consent?"
    And I click "Yes, Delete"
    And should see "Consent deleted" flash message
    And I proceed as the User
    When click "Create An Account"
    Then I should see "Some consents were changed. Please reload the page."
    And I should not see "Test Consent"
    And I should see 2 elements "Required Consent"
    And I proceed as the Admin
    And I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name        | Test Consent 2    |
      | Type        | Mandatory         |
      | Web Catalog | Store and Process |
    And I click "Test Node"
    When I save and close form
    Then should see "Consent has been created" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And click "Add Consent"
    And I choose Consent "Test Consent 2" in 4 row
    And click "Save settings"
    # Proceeding to the registration form
    And I proceed as the User
    And I am on the homepage
    When click "Register"
    Then I should see 3 elements "Required Consent"
    And I fill form with:
      | Company Name                         | OroCommerce                 |
      | First Name                           | Branda                      |
      | Last Name                            | Sanborn                     |
      | Email Address                        | BrandaJSanborn2@example.org |
      | Password                             | BrandaJSanborn2@example.org |
      | Confirm Password                     | BrandaJSanborn2@example.org |
    And I click "Presenting Personal Data"
    And I scroll modal window to bottom
    And click "Agree"
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    And I click "Test Consent 2"
    And click "Agree"
    # Proceeding to management console
    And I proceed as the Admin
    And go to Marketing/ Landing Pages
    When click delete "Test CMS Page" in grid
    Then I should see "Are you sure you want to delete this Landing Page?"
    When I click "Yes, Delete"
    Then should see "Landing Page deleted" flash message
    And I proceed as the User
    When click "Create An Account"
    Then I should see "Some consents were changed. Please reload the page."

  @skip
  Scenario: Consent should not be visible when related CMS page was deleted
    Given I should not see "Test Consent 2"
    Then I should see 2 elements "Required Consent"

  Scenario: Create customer group
    Given I proceed as the Admin
    And go to Customers/ Customer Groups
    And click "Create Customer Group"
    And fill "Customer Group Form" with:
      | Name         | All customers |
      | Payment Term | net 10        |
    And click on OroCommerce in grid
    When save and close form
    Then should see "Customer group has been saved" flash message

  Scenario: Check mandatory consents on Checkout Page
    Given I proceed as the User
    And I signed in as AmandaRCole1@example.org on the store frontend
    And click "Account"
    And I click "Edit Profile Button"
    And fill form with:
      | Presenting Personal Data             | false |
      | Collecting and storing personal data | false |
      | Email Newsletters                    | false |
    And I save form
    When click "Yes, Decline"
    Then should see "Customer User profile updated" flash message
    And click "Quick Order Form"
    And fill "QuickAddForm" with:
      | SKU1 |Lenovo_Vibe1_sku|
    And I wait for products to load
    And fill "QuickAddForm" with:
      | QTY1 | 10  |
    When click "Create Order"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see 2 elements "Required Consent"
    And the "Presenting Personal Data" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    And I should not see "Email Newsletters"
    When click "Continue"
    Then I should see that "Required Consent" contains "This agreement is required"
    When I click on "Consent Link" with title "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title             | Presenting Personal Data |
      | Disabled okButton | Agree                    |
      | cancelButton      | Cancel                   |
    When click "Cancel"
    Then the "Presenting Personal Data" checkbox should not be checked
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Collecting and storing personal data" checkbox should be checked
    When click "Continue"
    Then I should see that "Required Consent" contains "This agreement is required"
    And I click "Presenting Personal Data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Presenting Personal Data" checkbox should be checked
    When click "Continue"
    Then I should see "Billing Information" in the "Checkout Step Title" element
    When on the "Billing Information" checkout step I go back to "Edit Customer Consents"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see "All mandatory consents were accepted."
    And I should not see "Presenting Personal Data"
    And I should not see "Collecting and storing personal data"

  Scenario: Add one more mandatory consent during checkout process
    Given I proceed as the Admin
    And I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Test Consent 3 |
      | Type | Mandatory      |
    When save and close form
    Then I should see "Consent has been created" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Customer/Consents" on configuration sidebar
    And click "Add Consent"
    And I choose Consent "Test Consent 3" in 5 row
    When click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that redirect was executed and flash message is appeared on checkout
    Given I proceed as the User
    And I click "Continue"
    And fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    When I click "Continue"
    Then I should see "You have been redirected to the Agreements page as a new mandatory consent has been added and requires your attention. Please, review and accept it to proceed." flash message and I close it
    And I should see "Agreements" in the "Checkout Step Title" element
    And I should see 1 elements "Required Consent"
    And I should see "Test Consent 3"
    When fill form with:
      | Test Consent 3 | true |
    Then I should not see a "Consent Popup" element
    And the "Test Consent 3" checkbox should be checked

  Scenario: Process checkout as registered customer user
    Given I click "Continue"
    And fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "Account"
    Then I should see "Unaccepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element

  Scenario: Check that Agreements step is presenting if it was shown previously
    Given I click "Account"
    And I click "Edit Profile Button"
    And I fill form with:
      | Test Consent 3 | false |
    When I save form
    Then I should see "UiWindow" with elements:
      | Title        | Data Protection                                                 |
      | Content      | Are you sure you want to decline the consents accepted earlier? |
      | okButton     | Yes, Decline                                                    |
      | cancelButton | No, Cancel                                                      |
    And I click "Yes, Decline"
    And I should see "Customer User profile updated" flash message
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 |Lenovo_Vibe1_sku|
    And I wait for products to load
    And fill "QuickAddForm" with:
      | QTY1 | 10  |
    And I click "Create Order"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see 1 elements "Required Consent"
    And the "Test Consent 3" checkbox should not be checked
    When I click "Account"
    And I click "Edit Profile Button"
    Then I fill form with:
      | Test Consent 3 | true |
    When I save form
    Then I should see "Customer User profile updated" flash message
    When I open shopping list widget
    And I click "View Details"
    And I click "Create Order"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see "All mandatory consents were accepted."
    And I click "Sign Out"

  Scenario: Enable guest shopping list and guest checkout settings
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    When I save form
    Then I should see "Configuration saved" flash message
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Remove Test Consent 2
    Given I go to System/ Consent Management
    And click delete "Test Consent 2" in grid
    And I should see "Are you sure you want to delete this consent?"
    When I click "Yes, Delete"
    Then I should not see "Test Consent 2"

  Scenario: Guest checks email confirmation required to proceed checkout
    Given I proceed as the User
    And I am on homepage
    And type "Lenovo_Vibe1_sku" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "Lenovo_Vibe1_sku" product
    And I should see "Product has been added to" flash message
    And I open page with shopping list "Shopping List"
    And click "Create Order"
    And I click "Create An Account"
    And I fill "Registration Form" with:
      | Company          | Company            |
      | First Name       | Sue                |
      | Last Name        | Jackson            |
      | Email Address    | Sue001@example.com |
      | Password         | Sue001@example.com |
      | Confirm Password | Sue001@example.com |
      | Test Consent 3 | true |
    And I click "Presenting Personal Data"
    And I scroll modal window to bottom
    And click "Agree"
    And I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    And click "Agree"
    When I click "Create an Account and Continue"
    Then I should see "Agreements"
    When click "Continue"
    Then I should see "Please confirm your email before continue checkout" flash message

  Scenario: Disable customer user registration confirmation on management console
    Given I proceed as the Admin
    Given go to System/ Configuration
    And follow "Commerce/Customer/Customer Users" on configuration sidebar
    And fill "Customer Users Registration Form" with:
      | Confirmation Required Default | false |
      | Confirmation Required         | false |
    And click "Save settings"

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Check mandatory consents on Checkout Page as unauthorized user
    Given I proceed as the User
    And I signed in as AmandaRCole1@example.org on the store frontend
    And I click "Sign Out"
    And I am on homepage
    And type "Lenovo_Vibe1_sku" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "Lenovo_Vibe1_sku" product
    And I should see "Product has been added to" flash message
    And I open page with shopping list "Shopping List"
    And click "Create Order"
    And I click "Continue as a Guest"
    And I should see "Agreements" in the "Checkout Step Title" element
    And I should see 3 elements "Required Consent"
    And the "Presenting Personal Data" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    And I should not see "Email Newsletters"
    When click "Continue"
    Then I should see that "Required Consent" contains "This agreement is required"
    When I click on "Consent Link" with title "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title             | Presenting Personal Data |
      | Disabled okButton | Agree                    |
      | cancelButton      | Cancel                   |
    When click "Cancel"
    Then the "Presenting Personal Data" checkbox should not be checked
    When I click "Collecting and storing personal data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Collecting and storing personal data" checkbox should be checked
    When click "Continue"
    Then I should see that "Required Consent" contains "This agreement is required"
    When I click "Presenting Personal Data"
    And I scroll modal window to bottom
    When click "Agree"
    Then I should not see a "Consent Popup" element
    And the "Presenting Personal Data" checkbox should be checked
    And I should see "Test Consent 3"
    And fill form with:
      | Test Consent 3 | true |
    When click "Continue"
    Then I should see "Billing Information" in the "Checkout Step Title" element
    When on the "Billing Information" checkout step I go back to "Edit Customer Consents"
    Then I should see "Agreements" in the "Checkout Step Title" element
    And I should see "All mandatory consents were accepted."
    And I should not see "Presenting Personal Data"
    And I should not see "Collecting and storing personal data"

  Scenario: Process checkout as guest customer user
    Given I click "Continue"
    And fill form with:
      | First Name      | Tester2         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I type "Tester2@test.com" in "Email Address"
    And I type "Tester2@test.com" in "Password"
    And I type "Tester2@test.com" in "Confirm Password"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When click "Account"
    Then should see a "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element

  Scenario: Set Secure URL and usual URL
    Given I proceed as the Admin
    And go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing Settings Form" with:
      | URL        | http://localhost  |
      | Secure URL | https://localhost |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Consent dialog should open after changing an URL
    Given I proceed as the User
    And I click "Sign Out"
    And I click "Register"
    When I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title             | Presenting Personal Data |
      | Disabled okButton | Agree                    |
      | cancelButton      | Cancel                   |
