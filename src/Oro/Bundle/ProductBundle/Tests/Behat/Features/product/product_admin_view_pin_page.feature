@regression
@ticket-BB-13303
@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Product admin view pin page
  In order to have fast access to some product view pages
  As administrator
  I need to make sure that the pinned product view page is available to fast acess

  Scenario: Pin product view page
    Given I login as administrator
    And I go to Products/ Products
    And I click View "shirt_main" in grid
    When I pin page
    Then "shirt_main - Shirt_1" link must be in pin holder

  Scenario: Follow pinned product view page
    Given I am on dashboard
    When follow "shirt_main - Shirt_1" link in pin holder
    Then I should be on Product View page

  Scenario: Unpin product view page
    Given I go to Products/ Products
    And I click View "shirt_main" in grid
    When I unpin page
    Then "shirt_main - Shirt_1" link must not be in pin holder

  Scenario: Pin multi-step form
    Given I go to Products/Products
    And click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |sku_for_pin_testing     |
      |Name            |name_for_pin_testing    |
    When I pin page
    Then I should see that "Create Product" pin is active
    When I go to Dashboards/Dashboard
    Then I should see that "Create Product" pin is inactive
    When I follow Create Product link in pin holder
    And click "Continue"
    Then I should see that "Create Product" pin is active
    And "Create Product Form" must contains values:
      |SKU             |sku_for_pin_testing     |
      |Name            |name_for_pin_testing    |
