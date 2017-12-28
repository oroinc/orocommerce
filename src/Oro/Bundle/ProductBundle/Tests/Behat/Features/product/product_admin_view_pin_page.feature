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
