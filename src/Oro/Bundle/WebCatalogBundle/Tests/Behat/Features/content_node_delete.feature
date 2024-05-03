@regression
@ticket-BB-22552
@fixture-OroWebCatalogBundle:content_node_with_references.yml

Feature: Content Node Delete
  As Admin user
  I need to be able to delete content node that has not references in menu, consents, config

  Scenario: Prepare main navigation menu
    Given I login as administrator
    Given I go to System/ Configuration
    When I follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Web Catalog" field
    And fill "Routing Settings Form" with:
      | Web Catalog | Default Web Catalog |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    And uncheck "Use default" for "Navigation Root" field
    And I expand "Clearance" in tree
    And I click on "By Brand"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Prepare main commerce menu item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And I click "Create Menu Item"
    When I fill "Commerce Menu Form" with:
      | Title       | Products Menu Item   |
      | Target Type | Content Node        |
      | Web Catalog | Default Web Catalog |
    And I click on "Products" in tree "Menu Update Content Node Field"
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check button when content node referenced in consents
    And I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid

  Scenario Outline:
    And I click on "<MenuItem>"
    And I should see "Delete" button
    And I should see "Delete Current Content Node Button" button disabled
    And I should see "Delete" button with attributes:
      | title | <Title> |
    Examples:
      | MenuItem            | Title                                                                                                                            |
      | Default Web Catalog | This node cannot be deleted because it is referenced in ”Consent 1” Consent Management                                           |
      | Products            | This node cannot be deleted because it is referenced in ”commerce_main_menu” Menu                                                |
      | By brand            | This node cannot be deleted because it is referenced in ”oro_web_catalog.navigation_root” Website Config                         |
      | Clearance           | This node cannot be deleted because it’s child node “By Brand” is referenced in ”oro_web_catalog.navigation_root” Website Config |
