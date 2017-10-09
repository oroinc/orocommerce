@fixture-OroPromotionBundle:promotion_crud.yml
Feature: Managing promotions
  In order to use promotions on front store
  As an Administrator
  I need to have an ability to create, view, update and delete promotion entity in admin area

  Scenario: Promotion creation
    Given I login as administrator
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    Then I should be on Promotion Create page
    When I save form
    Then I should see "Promotion Form" validation errors:
      | Name           | This value should not be blank. |
      | Sort Order     | This value should not be blank. |
      | Discount Value | This value should not be blank. |
    And I should see "Should be specified filters or added some products manually."
    When I fill "Promotion Form" with:
      | Name                         | Promotion name        |
      | Sort Order                   | 10                    |
      | Enabled                      | 1                     |
      | Stop Further Rule Processing | 1                     |
      | Use Coupons                  | Yes                   |
      | Discount Value               | 10.0                  |
      | Activate At (first)            | <DateTime:today>      |
      | Deactivate At (first)        | <DateTime:tomorrow>   |
      | Website                      | Default               |
      | Customer Group               | All Customers         |
      | Labels                       | Promotion label       |
      | Descriptions                 | Promotion description |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Promotion edit
    Given I should be on Promotion Edit page
    When I fill "Promotion Form" with:
      | Name | New promotion name |
    And I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Promotion delete
    When I go to Marketing / Promotions / Promotions
    Then number of records should be 1
    And I should see following records in grid:
      | New promotion name |
    When I click delete New promotion name in grid
    And I confirm deletion
    Then I should see "Promotion deleted" flash message
    And there is no records in grid

  Scenario: Promotion scopes validation for customer and customer group
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name           | Test promotion name |
      | Sort Order     | 10                  |
      | Discount Value | 10.0                |
      | Customer       | first customer      |
      | Customer Group | All Customers       |
    And I press "Add" in "Matching Items" section
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    Then I should see "Should be chosen only one field. Customer Group or Customer."
