@feature-BB-25449
@regression

Feature: Seller Info

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Fill Seller Info Configuration
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Websites
    And I click Configuration Default in grid
    And I follow "Commerce/Contacts/Seller Info" on configuration sidebar
    And I uncheck "Use Organization" for "Company Name" field
    And I uncheck "Use Organization" for "Business Address" field
    And I uncheck "Use Organization" for "Phone Number" field
    And I uncheck "Use Organization" for "Contact Email" field
    And I uncheck "Use Organization" for "Website" field
    And I uncheck "Use Organization" for "Tax ID" field
    When I fill form with:
      | Contact Email    | banang.com        |
      | Website          | local/uri/path    |
    Then I should see "This value is not a valid email address."
    And I should see "This value is not a valid URL."
    When I fill form with:
      | Company Name     | ORO               |
      | Business Address | Bangkok           |
      | Phone Number     | 082270345         |
      | Contact Email    | ba@nang.com       |
      | Website          | https://local.loc |
      | Tax ID           | 12345678          |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Display seller system variables in the email template
    Given I go to System/Emails/Templates
    When I filter Template Name as is equal to "customer_user_welcome_email"
    And I click "edit" on first row in grid
    Then I should see "{{ system.sellerBusinessAddress }} – Seller Business Address"
    And I should see "{{ system.sellerCompanyName }} – Seller Company Name"
    And I should see "{{ system.sellerContactEmail }} – Seller Contact Email"
    And I should see "{{ system.sellerPhoneNumber }} – Seller Phone Number"
    And I should see "{{ system.sellerTaxID }} – Seller tax ID"
    And I should see "{{ system.sellerWebsiteURL }} – Seller Website URL"
    When fill "Email Template Form" with:
      | Content | <p>{{ system.sellerCompanyName }}</p><br/><p>{{ system.sellerBusinessAddress }}</p><br/><p>{{ system.sellerPhoneNumber }}</p><br/><p>{{ system.sellerContactEmail }}</p><br/><p>{{ system.sellerWebsiteURL }}</p><br/><p>{{ system.sellerTaxID }}</p> |
    And I submit form
    Then I should see "Template saved" flash message

  Scenario: Disable Customer User Confirmation Required
    Given I go to System/Configuration
    And I follow "Commerce/Customer/Customer Users" on configuration sidebar
    And I uncheck "Use default" for "Confirmation Required" field
    And I uncheck "Confirmation Required"
    And I save form

  Scenario: Ensure seller system variables are correctly applied when sending an email
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Sign Up"
    And I fill "Registration Form" with:
      | Company Name     | OroCommerce              |
      | First Name       | FrontU                   |
      | Last Name        | LastN                    |
      | Email            | FrontULastN1@example.org |
      | Password         | FrontULastN1@example.org |
      | Confirm Password | FrontULastN1@example.org |
    And I click "Create Account"
    Then email with Subject "Welcome: FrontU LastN" containing the following was sent:
      | Body | ORO               |
      | Body | Bangkok           |
      | Body | 082270345         |
      | Body | ba@nang.com       |
      | Body | https://local.loc |
      | Body | 12345678          |
