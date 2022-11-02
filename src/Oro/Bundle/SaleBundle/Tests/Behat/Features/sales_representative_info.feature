@regression
@fixture-OroSaleBundle:sales-representative-info.yml
Feature: Sales Representative Info
  In order to provide the customer with the contact information of his primary assigned sales representative
  As an Administrator
  I want to enable displaying of sales rep contact information on the store frontend in the configuration

#  Description
#
#  Add a new configuration option on global, organization level to enable displaying the primary sales representative contact info.
#  Allow to configure what (whose) information to show - customer user owner, customer owner, or configured for the website. For the last option - allow to specify the contact info.
#  When enabled, show the contact information of the selected sales representative in the store frontend footer.
#  If "Allow User Configuration" option is enabled in the system configuration, give admin users the ability to modify their customer visible contact info:
#  Don't Display Contact Info
#  Use My Profile Data - the contact info should be constructed using the predefined template (first + last name, phone number(s), email)
#  Use System - the contact info will be taken from the "Contact Details" field in the system configuration
#  Enter Manually - when selected, show additional field "Customer Visible Contact Info" - textarea.
#  Show only the options that are enabled in the system configuration in "Available User Options".
#  This user's "self-configuration" should happen in the user configuration (My Configuration in the user menu).
#  Configuration
#  System Configuration
#  Add new page - System -> Configuration -> COMMERCE -> Sales -> Contacts.
#  Add new setting to the page System -> Configuration -> COMMERCE -> Sales -> Contacts:
#  Customer Visible Contact Info:
#  Display - drop-down, default value - "don't display contact info", levels - global/organization/website :
#  Don't Display Contact Info
#  Customer User Owner
#  Customer Owner
#  Pre-Configured
#  Contact Details - textareas, default value - no (empty), show this field only if "pre-configured" option was selected in the previous field, levels - global/organization/website
#  Allow User Configuration - checkbox, default value - checked, , levels - global/organization, hint -
#  When checked, the sales representatives will be able to choose one of the options enabled below.
#  Available User Options - multi-select, default selection - all options selected:
#  Don't Display Contact Info
#  Use User Profile Data
#  Enter Manually
#  User Configuration
#  Same page - System -> Configuration -> COMMERCE -> Sales -> Contacts.
#  Add new setting to the page System -> Configuration -> COMMERCE -> Sales -> Contacts:
#  Customer Visible Contact Info - drop-down, includes only the options enabled in "Available User Options" plus possibly "Use System" (see below), default value - see below, levels - user
#  Don't Display Contact Info
#  Use My Profile Data
#  Use System - this option is available only when "Display" setting in the system configuration is set to "Pre-Configured"
#  Enter Manually
#  Default value depends on teh "Display" field in the system configuration:
#  when "Don't Display Contact Info" -> then "Don't Display Contact Info"
#  when "Customer User Owner" -> then "Use My Profile Data"
#  when "Customer Owner" -> then "Use My Profile Data"
#  when "Pre-Configured" -> then "Use System"
#  Acceptance Criteria
#  Show that displaying sale rep info can be enabled/disabled per organization/globally
#  Show that each of the "Display" configuration options works (leads to showing contact info of different sales reps - customer user owner/customer owner/pre-configured on different levels)
#  Demonstrate how sales reps can choose one of the enabled options (when "allow user configuration" is on) and how that leads to showing different contact info on the store frontend.

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: System level - Display
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    And fill "Customer Visible Contact Info Form" with:
      | Display Default                  | true  |
      | Allow User Configuration Default | false |
      | Allow User Configuration         | false |
    And click "Save settings"
    And I proceed as the User
    When I signed in as AmandaRCole@example.org on the store frontend
    Then should not see "Sales Representative"
    And I proceed as the Admin
    When fill "Customer Visible Contact Info Form" with:
      | Display Default | false               |
      | Display         | Customer User Owner |
    And click "Save settings"
    Then should see "Configuration saved" flash message
    And I proceed as the User
    And reload the page
    Then should see an "Sales Representative Info" element
    And I should see "Sales Representative Info" block with:
      | Charlie Sheen        |
      | +380504445566        |
      | Charlie1@example.com |
    And I proceed as the Admin
    When fill "Customer Visible Contact Info Form" with:
      | Display | Customer Owner |
    And click "Save settings"
    And I proceed as the User
    And reload the page
    Then should see an "Sales Representative Info" element
    And I should see "Sales Representative Info" block with:
      | John Doe          |
      | admin@example.com |
    And I proceed as the Admin
    When fill "Customer Visible Contact Info Form" with:
      | Display                 | Pre-Configured                            |
      | Contact Details Default | false                                     |
      | Contact Details         | Name: Test Data <br> Email: Test@test.com |
    And click "Save settings"
    And I proceed as the User
    And reload the page
    Then should see an "Sales Representative Info" element
    And I should see "Sales Representative Info" block with:
      | Name: Test Data      |
      | Email: Test@test.com |

  Scenario: System level - Allow User Configuration
    Given I proceed as the Admin
    And go to System/ User Management/ Users
    When click configuration "Charlie1@example.com" in grid
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    Then I should see "Enter Manually" for "Customer Visible Contact Info Form" select
    And go to System/ Configuration
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    When fill "Customer Visible Contact Info Form" with:
      | Display                          | Customer User Owner |
      | Allow User Configuration Default | true                |
    And click "Save settings"
    And go to System/ User Management/ Users
    And click configuration "Charlie1@example.com" in grid
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    Then the "Use Organization" checkbox should be checked
    And I should see "Don't Display Contact Info" for "Customer Visible Contact Info Form" select
    And I should see "Use My Profile Data" for "Customer Visible Contact Info Form" select
    And I should see "Enter Manually" for "Customer Visible Contact Info Form" select
    When fill "Customer Visible Contact Info Form" with:
      | Customer Visible Contact Info Organization | false                      |
      | Customer Visible Contact Info              | Don't Display Contact Info |
    And click "Save settings"
    And I proceed as the User
    And reload the page
    Then should not see "Sales Representative"
    And I proceed as the Admin
    When fill "Customer Visible Contact Info Form" with:
      | Customer Visible Contact Info Organization | false               |
      | Customer Visible Contact Info              | Use My Profile Data |
    And click "Save settings"
    And I proceed as the User
    And reload the page
    Then should see an "Sales Representative Info" element
    And I should see "Sales Representative Info" block with:
      | Charlie Sheen        |
      | +380504445566        |
      | Charlie1@example.com |
    And I proceed as the Admin
    When fill "Customer Visible Contact Info Form" with:
      | Customer Visible Contact Info Organization | false                                   |
      | Customer Visible Contact Info              | Enter Manually                          |
      | Enter Contact Info                         | Name: New Data <br> Email: new@test.com |
    And click "Save settings"
    And I proceed as the User
    And reload the page
    Then should see an "Sales Representative Info" element
    And I should see "Sales Representative Info" block with:
      | Name: New Data      |
      | Email: new@test.com |
    And I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    When fill "Customer Visible Contact Info Form" with:
      | Available User Options Default | false          |
      | Available User Options         | Enter Manually |
    And click "Save settings"
    And go to System/ User Management/ Users
    When click configuration "Charlie1@example.com" in grid
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    Then I should not see "Don't Display Contact Info" for "Customer Visible Contact Info Form" select
    And I should not see "Use My Profile Data" for "Customer Visible Contact Info Form" select
    And I should see "Enter Manually" for "Customer Visible Contact Info Form" select

  Scenario: System level - Guest Contact
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Sales/Contacts" on configuration sidebar
    When fill "Customer Visible Contact Info Form" with:
      | Guest Contact Default | false              |
      | Guest Contact         | Test guest contact |
    And click "Save settings"
    And I proceed as the User
    And click "Sign Out"
    And I should see "Sales Representative Info" block with:
      | Test guest contact |

