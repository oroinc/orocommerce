@regression
@ticket-BB-8491
@automatically-ticket-tagged
Feature: About page availability
  In order to get useful information
  As user
  I need to be able to access About page

  Scenario: Access About page
    Given I am on the homepage
    When I follow "About"
    Then Page title equals to "About"
    And image "About Page Image" is loaded
