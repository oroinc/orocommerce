@feature-BB-21439
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - with target System Page

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

  Scenario: Check validation errors
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases     | [search_term]                |
      | Action      | Redirect to a different page |
      | Target Type | System Page                  |
    And I save and close form
    Then I should see validation errors:
      | System Page | This value should not be blank. |

  Scenario: Create Search Term (301 Redirect - false)
    When I fill "Search Term Form" with:
      | System Page  | Oro Contactus Bridge Contact Us Page (Contact Us) |
      | 301 Redirect | false                                             |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases      | [search_term]                |
      | Action       | Redirect to a different page |
      | Target Type  | System Page                  |
      | 301 Redirect | No                           |
      | System Page  | Contact Us                   |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases     | Action                              | Restrictions                                                 |
      | search_term | Redirect to system page: Contact Us | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any |
    And I set alias "main" for the current browser tab
    When I click "Contact Us"
    Then a new browser tab is opened and I switch to it
    And Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/contact-us"
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will be forwarded to the Contact Us page
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the Contact Us page
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/product/search"

  Scenario: Update the Search Term (301 Redirect - true)
    Given I proceed as the Admin
    When I click edit "search_term" in grid
    And I fill "Search Term Form" with:
      | 301 Redirect | true |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | 301 Redirect | Yes |

  Scenario: Authorized user will be redirected to the Contact Us page
    Given I proceed as the Buyer
    When I reload the page
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/contact-us"

  Scenario: Unauthorized user will be redirected to the Contact Us page
    When I click "Account Dropdown"
    And click "Sign Out"
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/contact-us"
