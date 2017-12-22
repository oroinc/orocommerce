@regression
@ticket-BB-9207
@automatically-ticket-tagged
Feature: Create product
  In order to manage products
  As administrator
  I need to be able to create product

  Scenario: "Product 1A" > CHECK ABILITY TO GO TO THE SECOND STEP PRODUCT CREATION FORM DURING SUBMIT BY PRESSING ENTER KEY.
    Given I login as administrator
    And go to Products/ Products
    And click "Create Product"
    When I focus on "Type" field and press Enter key
    Then I should see "Save and Close"
