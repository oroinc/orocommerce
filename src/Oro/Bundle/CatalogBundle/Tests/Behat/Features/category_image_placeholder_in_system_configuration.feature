@regression
@ticket-BB-16135
@ticket-BB-16669
@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:highlighting_new_products.yml
Feature: Category Image Placeholder in system configuration
  In order to manage category images
  As an Administrator
  I want to be able to override the default placeholder for category image in the system configuration

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check no image on the home page
    Given I proceed as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    Then should see "3 items" for "NewCategory" category
    And should see an "Empty Featured Category Image" element
    And should not see an "Uploaded Featured Category Image" element

  Scenario: Change the product image placeholder
    Given I proceed as the Admin
    And login as administrator
    And go to System/Configuration
    And follow "Commerce/Design/Theme" on configuration sidebar
    When uncheck "Use default" for "Category Image Placeholder" field
    And fill "Category Image Placeholder Config" with:
      | Image | example1.xcf |
    And save form
    Then I should see "Category Image Placeholder Config" validation errors:
      | Image | This file is not a valid image. |
    And fill "Category Image Placeholder Config" with:
      | Image | cat1.jpg |
    And save form
    Then should see "Configuration saved" flash message

  Scenario: Check the product image placeholder on the product grid
    Given I proceed as the Buyer
    When reload the page
    Then should see "3 items" for "NewCategory" category
    And should see an "Uploaded Featured Category Image" element
    And should not see an "Empty Featured Category Image" element
