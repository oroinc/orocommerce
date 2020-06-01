@fixture-OroPromotionBundle:product-for-promotion.yml

Feature: Promotions Grid With Promotions ACL Enabled
  In order to view promotions
  As a backend user
  I need the promotions grid to show promotions when I have the rights to view them

  Scenario: Create backend user with the rights to view promotions, create promotion
    Given I login as administrator
    And go to System/User Management/Roles
    And click "Create Role"
    And fill form with:
      | Role | Promotions View |
    And select following permissions:
      | Promotion | View:Global |
    And save and close form
    And I should see "Role saved" flash message
    And go to System/User Management/Roles
    And click "Create Role"
    And fill form with:
      | Role | Promotions NonView |
    And select following permissions:
      | Promotion | View:None |
    And save and close form
    And I should see "Role saved" flash message

    Then I go to System/User Management/Users
    And click "Create User"
    And fill form with:
      | Enabled           | Enabled                 |
      | Username          | Promotions1@example.org |
      | Password          | Promotions1@example.org |
      | Re-enter password | Promotions1@example.org |
      | Primary Email     | Promotions1@example.org |
      | First name        | Promotions              |
      | Last name         | User                    |
      | ORO               | true                    |
      | Promotions View   | true                    |
      | Promotions NonView| true                    |
    And save and close form
    And I should see "User saved" flash message

    Then I go to Marketing/Promotions/Promotions
    And click "Create Promotion"
    And fill "Promotion Form" with:
      | Name           | Sample promotion |
      | Sort Order     | 1                |
      | Discount Value | 10               |
    And I press "Add" in "Items To Discount" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And save and close form
    And I should see "Promotion has been saved" flash message
    When click "Duplicate"
    Then should see "Promotion has been duplicated" flash message

  Scenario: Check the promotion is visible in the grid
    Given I login as "Promotions1@example.org" user
    And go to Marketing/Promotions/Promotions
    Then I should see "Sample promotion" in grid
    And there are two records in grid
    And click view "Sample promotion" in grid
    Then I should see "Promotions / Sample promotion"
