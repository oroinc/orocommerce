@regression
@fixture-OroCatalogBundle:delete_category_preserves_siblings.yml

Feature: Delete category preserves sibling categories

  Scenario: Delete category with children should not affect siblings
    Given I login as administrator
    When I go to Products/Master Catalog
    And I expand "Parent Category" in tree
    Then I should see "Category To Delete" belongs to "Parent Category" in tree
    And I should see "Sibling Category" belongs to "Parent Category" in tree
    When I expand "Category To Delete" in tree
    Then I should see "Child Category" belongs to "Category To Delete" in tree
    When I click "Category To Delete"
    And I click "Delete"
    And I click "Yes, Delete" in modal window
    Then I should see "Category deleted" flash message
    And I should see "Sibling Category" belongs to "Parent Category" in tree
    And I should not see "Category To Delete" belongs to "Parent Category" in tree
