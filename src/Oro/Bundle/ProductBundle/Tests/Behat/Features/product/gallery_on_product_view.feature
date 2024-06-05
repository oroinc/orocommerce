@fixture-OroProductBundle:product_listing_images.yml

Feature: Gallery on product view
  As an Administrator
  I want to be able to use popup for image gallery

  Scenario: Check gallery popup is present on front store
    Given I login as administrator
    And I go to Products / Products
    And I click Edit "PSKU1" in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I set Images with:
      | Main  | Listing | Additional |
      |       |         | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid
    And I save and close form
    And I should see "Product has been saved" flash message
    And I am on the homepage
    When I type "PSKU1" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see an "Product View Gallery Trigger" element
    And I click "Product View Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element
