@ticket-BB-17691
@fixture-OroProductBundle:product_listing_images.yml

Feature: Product images order
  In order to have best user experience for working with product images
  As a Buyer
  I want always see product images in the same order

  Scenario: Check product images order in admin panel
    Given I login as administrator
    And I go to Products / Products
    And I click Edit "PSKU1" in grid
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg |       |         | 1          |
      | cat2.jpg |       |         | 1          |
      | cat3.jpg |       | 1       | 1          |
      | cat1.jpg | 1     |         | 1          |
      | cat2.jpg |       |         | 1          |
      | cat3.jpg |       |         | 1          |
    And I save form
    And I should see "Product has been saved" flash message
    And I remember images order in "Product Images Table" element
    And I remember "listing" image resized ID
    And I remember "main" image resized ID
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see images in "Product Images Table" element in remembered order

  Scenario: Check product images order on the front store product grid
    Given I am on the homepage
    And I type "PSKU1" in "search"
    And click "Search Button"
    And I open product gallery for "PSKU1" product
    And I wait popup widget is initialized
    Then I should see remembered "listing" image in "Active Slide" element
    And I should see images in "Popup Gallery Widget" element in remembered order

  Scenario: Check product images order on the front store product view page
    Given I open product with sku "PSKU1" on the store frontend
    When I click on "Active Slide"
    Then I should see remembered "main" image in "Active Slide" element
    And I should see images in "Popup Gallery Widget" element in remembered order
