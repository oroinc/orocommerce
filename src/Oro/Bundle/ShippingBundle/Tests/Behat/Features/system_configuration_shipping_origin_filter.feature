@ticket-BAP-17525

Feature: System configuration shipping origin filter
  In order to have ability to manage Shipping Origin fast
  As a Administrator
  I should have an ability to filter system configuration by shipping origin information

  Scenario: Fill Shipping Origin with initial data
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Shipping Origin" on configuration sidebar
    And I should not see "Shipping Choose State Select"
    When I fill "Shipping Origin System Config Form" with:
      | Use Default | true |
    And I should not see "Shipping Choose State Select"
    And I click "Save settings"
    And I should not see "Shipping Choose State Select"
    When I fill "Shipping Origin System Config Form" with:
      | Use Default      | false         |
      | Country          | United States |
      | Region/State     | California    |
      | Zip/Postal Code  | 90401         |
      | City             | Santa Monica  |
      | Street Address 1 | 1685 Main St. |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: System Config search must not hide complex forms
    Given I reload the page
    When I type "address" in "Configuration Quick Search"
    Then I should see "Shipping Origin" in the "Configuration Sidebar Content" element
    And I should see "Country"
    And I should see "Region/State"
    And I should see "Zip/Postal Code"
    And I should see "City"
    And I should see "Street Address 1"
    And I should see "Street Address 2"
    And I should see "Highlighted Text" element with text "address" inside "Shipping Origin System Config Form" element

  Scenario Outline: System Config search must not hide complex forms
    When I type "<filter>" in "Configuration Quick Search"
    Then I should see "Shipping Origin" in the "Configuration Sidebar Content" element
    And I should see "Country"
    And I should see "Region/State"
    And I should see "Zip/Postal Code"
    And I should see "City"
    And I should see "Street Address 1"
    And I should see "Street Address 2"

    Examples:
      | filter      |
      | country     |
      | Postal Code |
      | city        |
      | region      |
      | street      |
      | 90401       |
      | Monica      |
      | 1685 Main   |
