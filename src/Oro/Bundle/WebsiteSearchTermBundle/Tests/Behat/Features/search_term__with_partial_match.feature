@feature-BB-21439
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - with Partial Match

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

  Scenario: Create Search Term
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases       | [search_term]                                     |
      | Action        | Redirect to a different page                      |
      | Target Type   | System Page                                       |
      | Partial match | false                                             |
      | System Page   | Oro Contactus Bridge Contact Us Page (Contact Us) |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases       | [search_term]                |
      | Partial match | No                           |
      | Action        | Redirect to a different page |
      | Target Type   | System Page                  |
      | System Page   | Contact Us                   |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Create Search Term with partial match
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases       | [search_term_partial_match]                        |
      | Action        | Redirect to a different page                       |
      | Target Type   | System Page                                        |
      | Partial match | true                                               |
      | 301 Redirect  | true                                               |
      | System Page   | Oro Customer Customer User Security Login (Sign In) |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases       | [search_term_partial_match]  |
      | Partial match | Yes                          |
      | 301 Redirect  | Yes                          |
      | Action        | Redirect to a different page |
      | Target Type   | System Page                  |
      | System Page   | Sign In                       |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Unauthorized user will be forwarded to the Contact Us page (full match at first)
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the Contact Us page (full match at first)
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Contact Us"
    And I should see "Preferred contact method"
    And the url should match "/product/search"

  Scenario: Authorized user will be redirected to the Customer Profile page (partial match)
    When I type "term" in "search"
    And I click "Search Button"
    Then Page title equals to "Profile"
    And I should see "My Profile"
    And the url should match "/customer/profile"

  Scenario: Authorized user will be not redirected to the Customer Profile page as search phrase is too short
    When I type "te" in "search"
    And I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "There are no products"

  Scenario: Unauthorized user will be redirected to the Log In page (partial match)
    When I click "Sign Out"
    And I type "term" in "search"
    And I click "Search Button"
    Then Page title equals to "Sign In"
    And I should see "Create An Account"
    And the url should match "/customer/user/login"

  Scenario: Unauthorized user will not be redirected to the Log In page as search phrase is too short
    When I type "te" in "search"
    And I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "There are no products"
