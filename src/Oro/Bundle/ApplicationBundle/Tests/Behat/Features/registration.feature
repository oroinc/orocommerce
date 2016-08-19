Feature: New user registration
  In order to have ability make order
  As a shop client
  I need to register

  Scenario: Registration
    Given I am on homepage
    And I follow "Sign In"
    And I follow "Create An Account"
    And I fill "Registration" form with:
      | Company Name     | OroCommerce         |
      | First Name       | Charlie             |
      | Last Name        | SHeen               |
      | Email Address    | charlie@example.com |
      | Password         | charlie             |
      | Confirm Password | charlie             |
    When I press "Create An Account"
    Then I should see "Please check your email to complete registration"
