@regression
@ticket-BAP-17336
@fixture-OroUserBundle:UserLocalizations.yml
@fixture-OroUserBundle:user.yml
@fixture-OroUserBundle:manager.yml
@fixture-OroProductBundle:product_frontend_single_unit_mode.yml

Feature: Localized email notification for RFQ
  In order to process RFQ
  As an admin
  I should receive RFQ notification emails in predefined language

  Scenario: Prepare configuration with different languages on each level
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    When I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, German Localization, French Localization] |
      | Default Localization  | French Localization                                 |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / User Management / Organizations
    And click Configuration "Oro" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use System" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | English |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, German Localization, French Localization] |
      | Default Localization  | English                                             |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Every user from RFQ notifications list should get an email in a lang of his config
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "request_create_notification"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English RFQ Create Notification Subject |
      | Content | English RFQ Create Notification Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French RFQ Create Notification Subject |
      | Content | French RFQ Create Notification Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German RFQ Create Notification Subject |
      | Content | German RFQ Create Notification Body    |
    And I submit form
    Then I should see "Template saved" flash message

    When I go to Customers / Customers
    And I click edit "AmandaRCole" in grid
    And I fill form with:
      | Assigned Sales Representatives | [Ethan] |
    And I submit form
    Then I should see "Customer has been saved" flash message
    When I go to Customers / Customer Users
    And I click edit "AmandaRCole" in grid
    And I fill form with:
      | Assigned Sales Representatives | [Charlie] |
    And I submit form
    Then I should see "Customer User has been saved" flash message

    When I click Logout in user menu
    And I login as "ethan" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I click Logout in user menu
    And I login as "charlie" user
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | French Localization |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I proceed as the User
    And I login as AmandaRCole@example.org buyer
    And I click "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill form with:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Phone Number  | 72 669 62 82            |
      | Company       | Red Fox Tavern          |
      | Role          | Sauce cook              |
      | Notes         | Some                    |
      | PO Number     | PO Test 01              |
      | Assigned To   | Amanda Cole             |
    And I click "Submit Request"
    Then Email should contains the following:
      | To      | admin@example.com                       |
      | Subject | English RFQ Create Notification Subject |
      | Body    | English RFQ Create Notification Body    |
    And Email should contains the following:
      | To      | ethan@example.com                      |
      | Subject | German RFQ Create Notification Subject |
      | Body    | German RFQ Create Notification Body    |
    And Email should contains the following:
      | To      | charlie@example.com                    |
      | Subject | French RFQ Create Notification Subject |
      | Body    | French RFQ Create Notification Body    |

  Scenario: On request's submit a Guest should get an email in a current language of website
    Given I proceed as the Admin
    When I click Logout in user menu
    And I login as administrator
    And I go to System / Emails / Templates
    And I filter Template Name as is equal to "request_create_confirmation"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English RFQ Create Confirmation Subject |
      | Content | English RFQ Create Confirmation Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French RFQ Create Confirmation Subject |
      | Content | French RFQ Create Confirmation Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German RFQ Create Confirmation Subject |
      | Content | German RFQ Create Confirmation Body    |
    And I submit form
    Then I should see "Template saved" flash message

    When I go to System / Configuration
    And I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And uncheck "Use default" for "Enable Guest RFQ" field
    And I check "Enable Guest RFQ"
    And I submit form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And I submit form
    Then I should see "Configuration saved" flash message

    When I proceed as the User
    And I click "Sign Out"
    And I click "Localization Switcher"
    And I select "French Localization" localization
    And type "Product" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    When I open shopping list widget
    And I click "Shopping List" on shopping list widget
    And click "Request Quote"
    And I fill form with:
      | First Name    | Tester                |
      | Last Name     | Testerson             |
      | Email Address | testerson@example.com |
      | Phone Number  | 72 669 62 82          |
      | Company       | Red Fox Tavern        |
      | Role          | CEO                   |
      | Notes         | Test note for quote.  |
      | PO Number     | PO Test 01            |
    And click "Edit"
    And I fill in "TargetPriceField" with "10,99"
    And click "Update"
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And Email should contains the following:
      | To      | testerson@example.com                  |
      | Subject | French RFQ Create Confirmation Subject |
      | Body    | French RFQ Create Confirmation Body    |
