@ticket-BB-14564
@fixture-OroUserBundle:manager.yml
@fixture-OroPricingBundle:Pricelists.yml

Feature: Price list duplicate visibility
  In order to check visibility for duplicate price list button
  As an Administrator
  I should not see duplicate button if role does not have appropriate permissions

  Scenario: Create different window session
    Given sessions active:
      | admin    |first_session |
      | manager  |second_session|

  Scenario: Check button visibility for admin role
    Given I proceed as the admin
    And login as administrator
    And go to Sales / Price Lists
    When I click on first price list in grid
    Then I should see following buttons:
      | Duplicate Price List |

  Scenario: Check button visibility for manager role
    Given I go to System / User Management / Roles
    And I click edit "Sales Manager" in grid
    And select following permissions:
      | Price List | Create:None |
    And I save form
    When I proceed as the manager
    And I login as "ethan" user
    And go to Sales / Price Lists
    When I click on first price list in grid
    Then I should not see following buttons:
      | Duplicate Price List |
