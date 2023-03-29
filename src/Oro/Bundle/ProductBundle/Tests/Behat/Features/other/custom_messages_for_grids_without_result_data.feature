@behat-test-env
@ticket-BAP-17428
@fixture-OroProductBundle:CustomEmptyGridTranslation.yml

Feature: Custom messages for grids without result data
  In order to provide possibility to configure datagrids
  by adding messages which will be shown when grid result is empty

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    When I login as administrator

  Scenario: Empty grid custom message
    Given I proceed as the Buyer
    When I go to the homepage
    And I type "Not found search" in "search"
    And click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "Empty grid custom message"

  Scenario: Empty grid custom message after apply filter
    When I filter "Any Text" as contains "Any not existing text"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "Empty filtered grid custom message"
