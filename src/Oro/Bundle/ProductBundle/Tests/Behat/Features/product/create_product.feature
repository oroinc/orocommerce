@regression
@ticket-BB-9207
@ticket-BB-17327
@ticket-BB-17630
@automatically-ticket-tagged
@fixture-OroCatalogBundle:categories.yml
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

  Scenario: Check second step of product creation form
    Given I go to Products/ Products
    And I click "Create Product"
    And I click "Retail Supplies"
    When I click "Continue"
    Then I should see "Type: Simple Product Family: Default Category: All Products / Retail Supplies"

  Scenario: Finalizing product creation
    Given fill "Create Product Form" with:
      | SKU              | Test123      |
      | Name             | Test Product |
      | Status           | Enable       |
      | Unit Of Quantity | item         |
    And I set Images with:
      | File     | Main | Listing | Additional |
      | cat1.jpg | 1    |         | 1          |
      | cat2.jpg |      | 1       | 1          |
    When save form
    Then I should see "Product has been saved" flash message
    And I remember "listing" image resized ID
    And I remember "main" image resized ID

  Scenario: Check created product on grid
    Given I go to Products/ Products
    When I filter SKU as is equal to "Test123"
    Then I should see remembered "listing" image for product with "Test123"
    And I should not see remembered "main" image for product with "Test123"

    When I click on Image cell in grid row contains "Test123"
    Then I should see remembered "main" image preview
    And I close large image preview

  Scenario: Check created product on view page
    When I click view "Test123" in grid
    And I should see product with:
      | SKU            | Test123      |
      | Name           | Test Product |
      | Type           | Simple       |
      | Product Family | Default      |

  Scenario: Disable guest access and check product image is still visible on grid and form
    Given I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    When uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    And I save form
    Then I should see "Configuration Saved" flash message

    When I go to Products/ Products
    And I filter SKU as is equal to "Test123"
    Then I should see remembered "listing" image for product with "Test123"
    And I should not see remembered "main" image for product with "Test123"

    When I click on Image cell in grid row contains "Test123"
    Then I should see remembered "main" image preview
    And I close large image preview

    When I click edit "Test123" in grid
    Then I should see remembered "main" image in "Product Form Images Section" element
    And I should see remembered "listing" image in "Product Form Images Section" element
