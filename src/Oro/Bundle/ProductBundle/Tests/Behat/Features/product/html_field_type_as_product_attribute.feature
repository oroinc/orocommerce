@regression
@fixture-OroProductBundle:quick_order_product.yml
Feature: HTML field type as product attribute
  In order to add new product attribute with ability play video
  As Administrator
  I need to create HTML field type as product attribute
  As Frontend User
  I need to be able watch video

  Scenario: Create HTML type field from entity management
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Product"
    And I click view "Product" in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | html_embed   |
      | Storage Type | Table column |
      | Type         | HTML         |
    And click "Continue"
    When I save and close form
    Then I should see "Field saved" flash message
    And I click update schema
    And I should see Schema updated flash message

  Scenario: Create product attribute with HTML type
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | html_video  |
      | Type       | HTML        |
    And click "Continue"
    And fill form with:
      | Label      | HTML video |
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Add to default product family new attribute
    Given I go to Products/ Product Families
    And I click Edit Default in grid
    And set Attribute Groups with:
      | Label | Visible | Attributes   |
      | HTML  | true    | [HTML video] |
    When I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Edit product to fill new attribute value
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill form with:
      | HTML video   | <p id='html_escaped'>HTML Content <script>alert('malicious script')</script>here!</p> |
    When I save and close form
    Then I should see "Product has been saved" flash message
     And I should see "<p id='html_escaped'>HTML Content <script>alert('malicious script')</script>here!</p>"
     And I click logout in user menu

  Scenario: Open product view page on Front Store to see created attribute
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "HTML Content <script>alert('malicious script')</script>here!"
    And I should not see tag "script" inside "html_escaped" element
