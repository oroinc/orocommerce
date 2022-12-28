@regression
@ticket-BB-16135
@ticket-BB-16669
@feature-BAP-19790
@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:highlighting_new_products.yml
Feature: Product Image Placeholder in system configuration
  In order to manage product images
  As an Administrator
  I want to be able to to override the default placeholder for product image in the system configuration

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check no image on the product grid
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When click "NewCategory"
    Then should see "Empty Product Image" for "PSKU1" product
    And I should see picture for "PSKU1" product in the "ProductFrontendGrid"
    And should not see "Uploaded Product Image" for "PSKU1" product

  Scenario: Check no image on the view page
    When I click "View Details" for "PSKU1" product
    Then should see an "Empty Product Image" element
    And I should see product picture in the "Product View Media Gallery"
    And should not see an "Uploaded Product Image" element

  Scenario: Check no image on the shopping list page
    When I click "Add to Shopping List"
    Then should see 'Product has been added to "Shopping List"' flash message
    And follow "Shopping List"
    And should see an "Empty Product Image" element
    And should not see an "Uploaded Product Image" element

  Scenario: Change the product image placeholder
    Given I proceed as the Admin
    And login as administrator
    And go to System/Configuration
    And follow "Commerce/Design/Theme" on configuration sidebar
    When uncheck "Use default" for "Product Image Placeholder" field
    And fill "Product Image Placeholder Config" with:
      | Image | example1.xcf |
    And save form
    Then I should see "Product Image Placeholder Config" validation errors:
      | Image | This file is not a valid image. |
    And fill "Product Image Placeholder Config" with:
      | Image | cat1.jpg |
    And save form
    Then should see "Configuration saved" flash message

  Scenario: Check the product image placeholder on the product grid
    Given I proceed as the Buyer
    When click "NewCategory"
    Then should see "Uploaded Product Image" for "PSKU1" product
    And I should see picture for "PSKU1" product in the "ProductFrontendGrid"
    And should not see "Empty Product Image" for "PSKU1" product

  Scenario: Check the product image placeholder on the view page
    When I click "View Details" for "PSKU1" product
    Then should see an "Uploaded Product Image" element
    And I should see product picture in the "Product View Media Gallery"
    And should not see an "Empty Product Image" element

  Scenario: Check the product image placeholder on the shopping list page
    When I open page with shopping list "Shopping List"
    Then should see an "Uploaded Product Image" element
    And should not see an "Empty Product Image" element
