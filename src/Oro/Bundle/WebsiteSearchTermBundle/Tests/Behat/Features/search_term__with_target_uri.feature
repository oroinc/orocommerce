@feature-BB-21439
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - with target URI

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check validation errors
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases     | [external_uri]               |
      | Action      | Redirect to a different page |
      | Target Type | URI                          |
    And I save and close form
    Then I should see validation errors:
      | URI | This value should not be blank. |

  Scenario: Create a Search Term with external url
    When I fill "Search Term Form" with:
      | URI | http://non-existing-url.local/someroute |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases     | [external_uri]                          |
      | Action      | Redirect to a different page            |
      | Target Type | URI                                     |
      | URI         | http://non-existing-url.local/someroute |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Create a Search Term with internal url
    Given I go to Marketing / Search / Search Terms
    When click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases     | [internal_uri]               |
      | Action      | Redirect to a different page |
      | Target Type | URI                          |
    And I fill "URI" with absolute URL "/product/search?search=sample_phrase" in form "Search Term Form"
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases     | [internal_uri]               |
      | Action      | Redirect to a different page |
      | Target Type | URI                          |
    And I should see "/product/search?search=sample_phrase" in the "Search Term Redirect URI" element
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Check the search term record in datagrid
    Given I go to Marketing / Search / Search Terms
    When filter Phrases as is equal to "external_uri"
    Then I should see following grid:
      | Phrases      | Action                                               | Restrictions                                                 |
      | external_uri | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any |
    And I set alias "main" for the current browser tab
    When I click "someroute"
    Then a new browser tab is opened and I switch to it
    And the url should match "/someroute"
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will be redirected to the external URI
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "external_uri" in "search"
    And I click "Search Button"
    Then the url should match "/someroute"

  Scenario: Unauthorized user will be redirected to the internal URI
    When I am on the homepage
    And I type "internal_uri" in "search"
    And I click "Search Button"
    Then the url should match "/product/search"
    And I should see "Search Results for \"sample_phrase\""

  Scenario: Authorized user will be redirected to the external URI
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "external_uri" in "search"
    And I click "Search Button"
    Then the url should match "/someroute"

  Scenario: Authorized user will be redirected to the internal URI
    When I am on the homepage
    And I type "internal_uri" in "search"
    And I click "Search Button"
    Then the url should match "/product/search"
    And I should see "Search Results for \"sample_phrase\""
