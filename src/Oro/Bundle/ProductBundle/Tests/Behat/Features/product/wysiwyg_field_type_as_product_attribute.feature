@regression
@fixture-OroProductBundle:quick_order_product.yml
Feature: WYSIWYG field type as product attribute
  In order to add new product attribute with ability play video
  As Administrator
  I need to create WYSIWYG field type as product attribute
  As Frontend User
  I need to be able watch video

  Scenario: Create WYSIWYG type field from entity management
    Given I login as administrator
    When I go to System/ Entities/ Entity Management
    And I filter "Name" as is equal to "Product"
    And I click "view" on row "Product" in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | WYSIWYG_embed |
      | Storage Type | Table column  |
      | Type         | WYSIWYG       |
    And click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Create product attribute with WYSIWYG type
    When I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | WYSIWYG_video  |
      | Type       | WYSIWYG        |
    And click "Continue"
    And fill form with:
      | Label      | WYSIWYG video |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Add to default product family new attribute
    Given I go to Products/ Product Families
    When I click "edit" on row "Default" in grid
    And set Attribute Groups with:
      | Label   | Visible | Attributes      |
      | WYSIWYG | true    | [WYSIWYG video] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Edit product to fill new attribute value
    Given I go to Products/ Products
    When click "edit" on row "PSKU1" in grid
    And I fill in WYSIWYG "Product WYSIWYG_video Attribute Content" with "<p id='WYSIWYG_escaped'>WYSIWYG Content <span>here!</span></p>"
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should not see alert
    And I should see "<p id='WYSIWYG_escaped'>WYSIWYG Content <span>here!</span></p>"
    And I click logout in user menu

  Scenario: Open product view page on Front Store to see created attribute
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "WYSIWYG Content here!"
