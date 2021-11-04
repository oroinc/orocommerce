@regression
@ticket-BB-20849
@fixture-OroWebCatalogBundle:web_catalog.yml

Feature: Content node with localized slugs
  Localized slugs are filled from localized titles after change fallback value

  Scenario: Create content node with localized titles
    Given I login as administrator
    And go to Marketing/ Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid
    And click "Create Content Node"
    And uncheck "Inherit Parent"
    And click "Content Node Form Titles Fallbacks"
    And fill "Content Node" with:
      | Title                                      | Acme    |
      | Title English (United States) use fallback | false   |
      | Title English (United States) value        | AcmeEN  |
      | Restriction1 Website                       | Default |
    And click "Content Node Form Url Slug Fallbacks"
    And fill "Content Node" with:
      | URL Slugs English (United States) use fallback | true |
    And click on "Show Variants Dropdown"
    And click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Uncheck "Use default value" checkbox for English (United States) localization slug
    Given I click "Content Node Form Url Slug Fallbacks"
    Then "Content Node" must contains values:
      | URL Slugs English (United States) value | acme |
    When I fill "Content Node" with:
      | URL Slugs English (United States) use fallback | false |
    Then "Content Node" must contains values:
      | URL Slugs English (United States) value | acmeen |
