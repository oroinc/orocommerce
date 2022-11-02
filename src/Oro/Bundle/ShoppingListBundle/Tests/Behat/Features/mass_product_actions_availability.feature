@elasticsearch
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
    Given I login as AmandaRCole@example.org buyer
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
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions not available for configurable products
    Given I proceed as the Buyer
    When I type "shirt" in "search"
    And I click "Search Button"
    And I should not see mass action checkbox in row with shirt_main content for "Product Frontend Grid"
    And I should see mass action checkbox in row with gtsh_l content for "Product Frontend Grid"
    And I click "Copyright"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Non visible products can not be added to a newly created Shopping List with mass actions
    Given I proceed as the Buyer
    And I type "rtsh_m" in "search"
    And I click "Search Button"
    Then I should see "rtsh_m"
    And I check rtsh_m record in "Product Frontend Grid" grid

    When I proceed as the Admin
    And I go to Products/ Products
    And I click view rtsh_m in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

    When I proceed as the Buyer
    And I click "Create New Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    And I click "Create and Add"
    Then I should see "No products were added"
    And I reload the page
    And there is no records in "Product Frontend Grid"

  Scenario: Non visible products can not be added with mass actions
    Given I proceed as the Buyer
    And I go to homepage
    And I type "gtsh_l" in "search"
    And I click "Search Button"
    Then I should see "gtsh_l"
    And I check gtsh_l record in "Product Frontend Grid" grid

    When I proceed as the Admin
    And I go to Products/ Products
    And I click view gtsh_l in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

    When I proceed as the Buyer
    And I click "Add to Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    Then I should see "No products were added"
    And I reload the page
    And there is no records in "Product Frontend Grid"
