@fixture-OroProductBundle:product_listing_images.yml
@regression
Feature: Display product listing image instead of main image on the home page
  In order to check type of the product image on the home page
  As an User
  I want to be sure that listing image is displayed instead of main image

  Scenario: Check that main image is displayed if there's no listing image
    Given I login as administrator
    And I go to Products / Products
    And I click Edit PSKU1 in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I fill form with:
      | Is Featured | Yes |
    And I save form
    Then I should see "Product has been saved" flash message
    And I remember "main" image filtered ID
    Then I am on homepage
    Then I should see remembered "main" image in "Top Selling Items" section
    Then I should see remembered "main" image in "Featured Products" section

  Scenario: Check that listing image is displayed if it is present
    Given I am on dashboard
    And I go to Products / Products
    And I click Edit PSKU1 in grid
    And I set Images with:
      | Main  | Listing | Additional |
      |       | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
    Then I click "Edit"
    And I remember "listing" image filtered ID
    Then I am on homepage
    Then I should see remembered "listing" image in "Top Selling Items" section
    Then I should see remembered "listing" image in "Featured Products" section
