@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Mass Product Actions Availability
  In order to allow mass actions only when expected
  As an Administrator
  I should be sure that mass actions available only in expected cases

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Mass actions are disabled for guest when Guest Shopping List feature is Disabled
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then "Shopping List Config" must contains values:
      | Enable Guest Shopping List | false |

    When I proceed as the Buyer
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I should not see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions are enabled for guest when Guest Shopping List feature is Enabled
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    When uncheck "Use default" for "Enable guest shopping list" field
    And I fill form with:
      | Enable Guest Shopping List | true |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Guest Shopping List | true |

    When I proceed as the Buyer
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions are enabled by default
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then "Shopping List Config" must contains values:
      | Enable Mass Adding on Product Listing | true |

  Scenario: Mass actions are disabled for Buyer when Enable Mass Adding On Product Listing feature is Disabled
    Given I proceed as the Admin
    When uncheck "Use default" for "Enable Mass Adding on Product Listing" field
    And I fill form with:
      | Enable Mass Adding on Product Listing | false |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Mass Adding on Product Listing | false |

    When I proceed as the Buyer
    And I login as AmandaRCole@example.org the "Buyer" at "second_session" session
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I should not see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions are enabled for Buyer when Enable Mass Adding On Product Listing feature is Enabled
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill form with:
      | Enable Mass Adding on Product Listing | true |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Mass Adding on Product Listing | true |

    When I proceed as the Buyer
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions not available for configurable product
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org the "Buyer" at "second_session" session
    When I type "shirt_main" in "search"
    And I click "Search Button"
    And I should not see mass action checkbox in row with shirt_main content for "Product Frontend Grid"
    And I should see mass action checkbox in row with gtsh_l content for "Product Frontend Grid"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"
