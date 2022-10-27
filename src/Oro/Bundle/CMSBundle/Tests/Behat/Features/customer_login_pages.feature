@regression
@ticket-BB-8491
Feature: Customer login pages
  In order to update information about 'customer login pages'
  As an administrator
  I need to be able to view & edit 'customer login page'

  Scenario: Edit & view 'customer login page'
    Given I login as administrator
    And go to Marketing / Customer Login Pages
    Then I should see following grid:
      | Id    |
      | 1     |
    And I click Edit 1 in grid
    And I fill form with:
      | Top Content      | some top content    |
      | Bottom Content   | some bottom content |
    And I should not see "CSS Styles"
    When I save and close form
    Then I should see "Login form has been saved" flash message
    And I should see Customer login page with:
      | Top Content      | some top content    |
      | Bottom Content   | some bottom content |
      | Logo             | N/A                 |
      | Background Image | N/A                 |
    And I should not see "CSS Styles"
