@regression
@fixture-OroProductBundle:quick_order_product.yml
Feature: After creating new product attribute products grid get 500 error
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Create product attribute
    Given I login as administrator
    And go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      |Field name|new|
      |Type      |Many to many |
    And click "Continue"
    And fill form with:
      |Label                     |new attribute|
      |Description               |test         |
      |Target entity             |Business Unit|
      |Related entity data fields|Id           |
      |Related entity info title |Id           |
      |Related entity detailed   |Id           |
      |Searchable                |Yes          |
      |Filterable                |Yes          |
      |Enabled                   |Yes          |
    When save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I click update schema
    When go to Products/ Products
    And I click "NewCategory"
    Then I should see "PSKU1"
