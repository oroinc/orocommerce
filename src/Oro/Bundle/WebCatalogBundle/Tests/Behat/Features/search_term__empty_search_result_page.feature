@feature-BB-21439
@fixture-OroWebCatalogBundle:content_nodes_for_empty_search_result_page.yml

Feature: Search Term - empty search result page

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    When uncheck "Use default" for "Enable Search Terms Management" field
    And I check "Enable Search Terms Management"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Set default node slugs
    When I go to Marketing/ Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Try to configure Empty Search Result Page on global level
    When I go to System/Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    And uncheck "Use default" for "Empty Search Result Page" field
    Then I should see "Please choose a Web Catalog"
    When I fill "Empty Search Result Page System Configuration Form" with:
      | Web Catalog | Default Web Catalog |
    And I click "Save settings"
    Then I should see "Empty Search Result Page System Configuration Form" validation errors:
      | Content Node | This value should not be blank. |

  Scenario: Only the web-nodes without restrictions by customer group, customer and localization could be selected
    When I click on "By Brand"
    And I click "Save settings"
    Then I should see "Empty Search Result Page System Configuration Form" validation errors:
      | Content Node | Content node cannot have restrictions |

  Scenario: Save Empty Search Result page configuration value on global level
    When I click on "New Arrivals"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Only the web-nodes without restrictions by customer group, customer, localization and website could be selected
    When I set "Default Web Catalog" as default web catalog
    And I go to System/ Websites
    And I click "Configuration" on row "Default" in grid
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    And uncheck "Use Organization" for "Empty Search Result Page" field
    Then I should not see "Please choose a Web Catalog"
    When I click "Save settings"
    Then I should see "Empty Search Result Page System Configuration Form" validation errors:
      | Content Node | Content node cannot have restrictions |

  Scenario: Save Empty Search Result page configuration value on website level
    When I click on "Clearance"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create Search Term
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases        | [search_term]            |
      | Action         | Show search results page |
      | Search Results | Original search results  |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases        | [search_term]            |
      | Action         | Show search results page |
      | Search Results | Original search results  |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Unauthorized user will not be forwarded to Empty Search Result Content Node when search term is found
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "search_term" in "search"
    And I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And the url should match "/product/search"

  Scenario: Unauthorized user will be forwarded to Empty Search Result Content Node when search term is not found
    When I type "noresults" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog / Clearance"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to Empty Search Result Content Node when search term is not found
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I type "search_term" in "search"
    And I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And the url should match "/product/search"

  Scenario: Authorized user will not be forwarded to Empty Search Result Content Node when search term is found
    When I type "noresults" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog / Clearance"
    And the url should match "/product/search"
