Feature: Contact us and About pages availability
  In order to get useful information
  As user
  I need to be able to access Contact us and About pages

  Scenario: Access Contact us page
    Given I am on the homepage
    When I follow "Contact Us"
    Then I am on page with title "Contact Us"

  Scenario: Access About page
    Given I am on the homepage
    When I follow "About"
    Then I am on page with title "About"
