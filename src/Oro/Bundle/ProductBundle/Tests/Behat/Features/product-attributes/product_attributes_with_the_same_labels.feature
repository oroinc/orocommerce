@ticket-BB-17957
Feature: Product attributes with the same labels
  In order to have custom product attributes with the same labels
  As an Administrator
  I want to be able to add product attributes with the same labels to product family

  Scenario: Prepare product attributes
    Given I login as administrator
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | custom_name_1 |
      | Type       | String        |
    And I click "Continue"
    And I fill form with:
      | Label | Name |
    And I save and create new form
    Then I should see "Attribute was successfully saved" flash message
    When I fill form with:
      | Field Name | custom_name_2 |
      | Type       | String        |
    And I click "Continue"
    And I fill form with:
      | Label | Name |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Add product attributes to product family
    Given I go to Products / Product Families
    And I click Edit "Default" in grid
    Then I should see "Name(custom_name_1)" for "General Group Attributes" select
    And I should see "Name(custom_name_2)" for "General Group Attributes" select
    When I set Attribute Groups with:
      | Label | Visible | Attributes                                 |
      | Other | true    | [Name(custom_name_1), Name(custom_name_2)] |
    And I save form
    Then I should see "Successfully updated" flash message
