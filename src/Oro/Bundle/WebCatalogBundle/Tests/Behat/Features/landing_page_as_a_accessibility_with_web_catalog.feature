@regression
@fixture-OroWebCatalogBundle:empty_web_catalog.yml

Feature: Landing Page as a Accessibility with web catalog

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check default Accessibility page
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see an "Accessibility Landing Page Link" element
    When I follow the skip to content accessibility link
    Then I should see "Accessibility" in the "Page Title" element
    And I should be on "/accessibility"

  Scenario: Create Web Catalog Content Node for Accessibility
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Add System Page"
    And I fill "Content Node" with:
      | Title | Root Node |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Accessibility node |
      | Slug  | accessibility-node |
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Accessibility |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Routing settings use default Accessibility page
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then "Routing Settings Form" must contains values:
      | Accessibility | Accessibility |
    Then I should not see an "Accessibility node" element

    When I fill "Routing Settings Form" with:
      | Web Catalog Use Default | false               |
      | Web Catalog             | Default Web Catalog |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that the "Accessibility" link is hidden when no Content Node is set, even with an active web catalog
    Given I proceed as the Buyer
    When I am on the homepage
    And I should not see an "Accessibility Landing Page Link" element

  Scenario: Configure Content Node for Accessibility in Routing settings
    Given I proceed as the Admin
    When uncheck "Use default" for "Accessibility Page" field
    And I click on "Accessibility node"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify Buyer is redirected to configured Accessibility node
    Given I proceed as the Buyer
    When I am on the homepage
    And I follow the skip to content accessibility link
    Then I should see "Accessibility node" in the "Page Title" element
    And I should be on "/accessibility-node"
