@regression
@ticket-BB-19815
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
@fixture-OroLocaleBundle:FrenchLocalization.yml

Feature: Content Node slug validation
  In order to update slug fields correctly when user typing default and localized titles
  As an administrator
  I should be able to see the correct generated default/ localized slug even when they are disabled
    once they follow between default value or localized titles by checking/ unchecking checkboxes.

  Scenario: Create content nodes tree
    Given I login as administrator
    And I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles | Web Catalog Root |
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products node |
      | Slug  | products-node |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click on "Content Node Titles Localization Form Fallbacks"
    And fill "Content Node Titles Localization Form" with:
      | Use Default             | false             |
      | English (United States) | Products node ENG |
    And I save form
    And I click on "Content Node Form Slug Fallbacks"
    Then "Content Node Form" must contains values:
      | First Url Slug | products-node-eng |

  Scenario: Create content node with the same URL Slug on the same level should trigger validation error
    Given I click "Web Catalog Root"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products duplicate |
      | Slug  | products-node      |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "URL Slug must be unique within the same parent Content Node"

  Scenario: Create content node with the same URL Slug in another case on the same level should trigger validation error
    And I fill "Content Node" with:
      | Slug | products-NODE |
    When I save form
    Then I should see "URL Slug must be unique within the same parent Content Node"

  Scenario: Create content node with the same URL Slug on another level should be possible
    Given I click "Products node"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products duplicate |
      | Slug  | products-NODE      |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Move content node with the same URL Slug
    When I drag and drop "Products duplicate" before "Products node"
    And I click "Apply" in modal window
    Then "Content Node" must contains values:
      | Slug | products-node-1 |

  Scenario: Enter localized titles to check slug is synced correctly
    Given I click "Products node"
    And I click on "Content Node Titles Localization Form Fallbacks"
    When I fill "Content Node Titles Localization Form" with:
      | French Default | false            |
      | French         | Products node FR |
    And I click on "Content Node Form Slug Fallbacks"
    Then "Content Node Form" must contains values:
      | Second Url Slug | products-node-fr  |
    And I fill "Content Node Form" with:
      | Second Url Slug Use Default | true  |
    And I save form
    When I click on "Content Node Titles Localization Form Fallbacks"
    And I fill "Content Node Titles Localization Form" with:
      | French         | Products node FR 2 |
    And I click on "Content Node Form Slug Fallbacks"
    Then "Content Node Form" must contains values:
      | Second Url Slug | products-node     |
    And I save form

  Scenario: Check/ Uncheck to use default slug between different languages and confirm slugify is working well
    Given I click on "Content Node Form Slug Fallbacks"
    And I fill "Content Node Form" with:
      | First Url Slug Use Default  | false |
      | Second Url Slug Use Default | true  |
    Then I click on "Content Node Titles Localization Form Fallbacks"
    When I fill "Content Node Titles Localization Form" with:
      | French         | Products node FR 3 |
    Then "Content Node Form" must contains values:
      | First Url Slug  | products-node-eng |
      | Second Url Slug | products-node     |
    And I fill "Content Node Form" with:
      | First Url Slug Use Default  | true  |
      | Second Url Slug Use Default | false |
    When I fill "Content Node Titles Localization Form" with:
      | French         | Products node FR 4 |
    Then "Content Node Form" must contains values:
      | First Url Slug  | products-node      |
      | Second Url Slug | products-node-fr-4 |
