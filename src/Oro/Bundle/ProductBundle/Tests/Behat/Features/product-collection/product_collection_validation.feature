@regression
@fixture-OroProductBundle:product_collection_add.yml
Feature: Product collection validation
  In order to be sure that added Product Collection is valid
  As and Administrator
  I want to have validation for invalid cases

  Scenario: Saving Product Collection with empty filters and no manual product, results in validation error
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    And I click "Save"
    Then I should see "Should be specified filters or added some products manually."

  Scenario: Saving Product Collection with added just manual products, is allowed
    When I click "All Added"
    And I click "Add Button"
    And I should see "Add Products"
    And I check PSKU2 record in "Add Products Popup" grid
    And I click "Add" in modal window
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Preview Another Product Collection with invalid state of Condition Builder, result in validation error
    When I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click on "Preview Results"
    Then I should see "Conditions in filters should not be blank."

  Scenario: Saving Another Product Collection with invalid state of Condition Builder, result in validation error
    When I save form
    Then I should not see text matching "You have changes in the Filters section that have not been applied"
    And I should see "Conditions in filters should not be blank."

  Scenario: Saving Another Product Collection with filled just filters, is allowed
    When I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I click on "Preview Results"
    And I fill "Content Node Form" with:
      | First System Page Customer Group | Non-Authenticated Visitors |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Adding Product Collection with duplicated name, results in validation error
    When I click "Content Variants"
    And I fill "Content Node Form" with:
      | Default Product Collection Segment Name | Some Product Collection Name |
    And I click on "Preview Results"
    And I save form
    Then I should see "Content Node has been saved" flash message
    Then I click "Content Variants"
    And I fill "Content Node Form" with:
      | Product Collection Segment Name | Some Product Collection Name |
    And I save form
    And I click "Content Variants"
    Then I should see "This name already in use"

  Scenario: Change new Product Collection name to unique, allow saving
    When I fill "Content Node Form" with:
      | Product Collection Segment Name | Unique Name |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Changing names to same for saved Product Collections, results in validation error
    When I click "Content Variants"
    And I fill "Content Node Form" with:
      | Default Product Collection Segment Name | Same Name |
      | Product Collection Segment Name         | Same Name |
    And I save form
    And I click "Content Variants"
    Then I should see "This name already in use"
