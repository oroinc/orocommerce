@fixture-OroProductBundle:product_listing_images.yml
@regression
Feature: Display product listing image instead of main image on the home page in blank theme
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Switch to blank theme
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And fill "Theme Form" with:
      | ThemeUseDefault | false       |
      | Theme           | Blank theme |
    And submit form

  Scenario: Check that main image is displayed if there's no listing image
    Given I login as administrator
    And I go to Products / Products
    And I click Edit PSKU1 in grid
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg | 1     | 1       | 1          |
    And I fill form with:
      | Is Featured | Yes |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Then I click "Edit"
    And I remember "main" image resized ID
    Then I am on homepage
    Then I should see remembered "main" image in "Top Selling Items" section
    Then I should see remembered "main" image in "Featured Products" section

  Scenario: Check that listing image is displayed if it is present
    Given I am on dashboard
    And I go to Products / Products
    And I click Edit PSKU1 in grid
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat2.jpg |       | 1       | 1          |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Then I click "Edit"
    And I remember "listing" image resized ID
    Then I am on homepage
    Then I should see remembered "listing" image in "Top Selling Items" section
    Then I should see remembered "listing" image in "Featured Products" section
