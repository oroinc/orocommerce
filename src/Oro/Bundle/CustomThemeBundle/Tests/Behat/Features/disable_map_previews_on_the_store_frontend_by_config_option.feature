@fixture-OroCustomerBundle:CustomerUserAddressFixture.yml
@regression
Feature: Disable map previews on the store frontend by config option
  In order to be able to disable map previews for addresses on the front store
  As an administrator
  I need to have a config option that will hide map previews on front store

  Scenario: Check default config value is on
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    Then Enable Map Preview field should has Yes value
    And I go to System/User Management/Organizations
    And I click view "Oro" in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    Then Enable Map Preview field should has Yes value
    And I go to System/Websites
    And I click on Default in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    Then Enable Map Preview field should has Yes value

  Scenario: Check that map previews are visible on the front end
    When I continue as the Buyer
    And I follow "Account"
    Then I should see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should see "Map Button" element inside "Customer Company Addresses Grid" element
    Then I should see "Map Button" element inside "Customer Company User Addresses Grid" element
    And I click "Map" on row "801 Scenic Hwy" in "Customer Company Addresses Grid"
    Then I should see an "Map Popover" element
    # Click on empty space to close the popover
    And I click on empty space
    And I click "Map" on row "801 Scenic Hwy" in "Customer Company User Addresses Grid"
    Then I should see an "Map Popover" element
    And I click on empty space

  Scenario: Check that map previews are visible on the front end with custom theme
    Given I operate as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And uncheck "Use default" for "Theme" field
    And I fill in "Theme" with "Custom theme"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    And I click "Address Book"
    Then I should see "Map Container" element inside "Customer Company Addresses List" element
    Then I should see "Map Container" element inside "Customer Company User Addresses List" element

  Scenario: Check that map previews are hidden when disabled in system config in custom theme
    Given I operate as the Admin
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use default" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Container" element inside "Customer Company Addresses List" element
    And I should not see "Map Container" element inside "Customer Company User Addresses List" element
    Then I operate as the Admin
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And check "Use default" for "Enable Map Preview" field
    And I save form

  Scenario: Check that map previews are hidden when disabled in organization config in custom theme
    Given I operate as the Admin
    Given I go to System/User Management/Organizations
    And I click view "Oro" in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use System" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Container" element inside "Customer Company Addresses List" element
    And I should not see "Map Container" element inside "Customer Company User Addresses List" element
    Then I operate as the Admin
    And I go to System/User Management/Organizations
    And I click view "Oro" in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And check "Use System" for "Enable Map Preview" field
    And I save form

  Scenario: Check that map previews are hidden when disabled in website config in custom theme
    Given I go to System/Websites
    And I click on Default in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use Organization" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Container" element inside "Customer Company Addresses List" element
    And I should not see "Map Container" element inside "Customer Company User Addresses List" element
    Then I operate as the Admin
    And I go to System/Websites
    And I click on Default in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And check "Use Organization" for "Enable Map Preview" field
    And I save form

  Scenario: Check that map previews are hidden when disabled in system config in default theme
    Given I go to System/Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And check "Use default" for "Theme" field
    And I save form
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use default" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Button" element inside "Customer Company Addresses Grid" element
    And I should not see "Map Button" element inside "Customer Company User Addresses Grid" element
    Then I operate as the Admin
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And check "Use default" for "Enable Map Preview" field
    And I save form

  Scenario: Check that map previews are hidden when disabled in organization config in default theme
    Given I go to System/User Management/Organizations
    And I click view "Oro" in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use System" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Button" element inside "Customer Company Addresses Grid" element
    And I should not see "Map Button" element inside "Customer Company User Addresses Grid" element
    Then I operate as the Admin
    And I go to System/User Management/Organizations
    And I click view "Oro" in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And check "Use System" for "Enable Map Preview" field
    And I save form

  Scenario: Check that map previews are hidden when disabled in website config in default theme
    Given I go to System/Websites
    And I click on Default in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use Organization" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "No"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I click "Address Book"
    Then I should not see "Map Button" element inside "Customer Company Addresses Grid" element
    Then I should not see "Map Button" element inside "Customer Company User Addresses Grid" element

  Scenario: Check that map previews and map icons are hidden when disabled (mobile view)
    Given I continue as the Buyer
    And I follow "Account"
    And I set window size to 375x640
    Then I should not see "Map Container" element inside "Default Addresses" element
    And I should not see "Map Icon" element inside "Default Addresses" element

  Scenario: Check that map previews and map icons are visible (mobile view)
    Given I operate as the Admin
    And I go to System/Websites
    And I click on Default in grid
    And I click "Organization Configuration"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use Organization" for "Enable Map Preview" field
    And I fill in "Enable Map Preview" with "Yes"
    And I save form
    Then I continue as the Buyer
    And I follow "Account"
    And I set window size to 375x640
    Then I should see "Map Icon" element inside "Default Addresses" element
    When I click on "Map Icon"
    And I should see an "Fullscreen Popup" element
    # Will be fixed in BB-14553
    # And I should see "Popup Map Container" element inside "Fullscreen Popup" element
    Then I set window size to 1440x900
    And I should not see an "Fullscreen Popup" element
    And I should not see "Map Icon" element inside "Default Addresses" element
    And I should see "Map Container" element inside "Default Addresses" element
