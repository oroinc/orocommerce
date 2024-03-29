@ticket-BAP-11270
@automatically-ticket-tagged
Feature: New user registration
  In order to have ability make order
  As a shop client
  I need to register

  Scenario: Registration
    Given I am on homepage
    And I follow "Log In"
    And I follow "Sign Up"
    And I fill "Registration Form" with:
      | Company Name     | OroCommerce         |
      | First Name       | Charlie             |
      | Last Name        | SHeen               |
      | Email Address    | charlie@example.com |
      | Password         | Charlie001          |
      | Confirm Password | Charlie001          |
    When I click "Create Account"
    Then I should see "Please check your email to complete registration"
