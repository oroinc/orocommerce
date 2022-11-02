@regression
@ticket-BB-21200
@ticket-BAP-21015

Feature: Schema update after removing new attribute
  Check the correctness of removing the new attribute without updating the schema after attribute creation.

  Scenario: Create product attribute
    Given I login as administrator
    And go to Products/ Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | BooleanField |
      | Type       | Boolean      |
    And click "Continue"
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    # Ignore schema update

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "BooleanField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    When I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    # any attribute in state NEW (that has not been applied against DB) will be deleted immediately w/o
    # requirement to update schema
    And should not see "Update schema"
