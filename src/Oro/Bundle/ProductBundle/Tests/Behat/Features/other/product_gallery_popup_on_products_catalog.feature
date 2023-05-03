@behat-test-env
@ticket-BB-13978
@ticket-BB-16275
@feature-BAP-19790
@ticket-BB-22109
@fixture-OroProductBundle:product_listing_images.yml

Feature: Product Gallery Popup On Products Catalog
  In order to see product images
  As a Buyer
  I want to have an ability to see main product image as preview and others in image gallery
  I want product images to have localized alt tags

  Scenario: Create different window session
    Given sessions active:
    | Admin |first_session  |
    | User  |second_session |
    # Load images to product to make it show in Top Selling Items Block
    And I proceed as the Admin
    And login as administrator
    And I go to Products/ Products
    When I click Edit "PSKU1" in grid
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
    Then I should see "Product has been saved" flash message
    # Enable localizations
    And I enable the existing localizations

  Scenario: Default state - "Enable Image Preview On Product Listing" is On
    Given I proceed as the User
    When I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    And I click "NewCategory"
    And I should see preview image with alt "Product1`\"'&йёщ®&reg;>" for "PSKU1" product
    And I should see picture for "PSKU1" product in the "ProductFrontendGrid"
    And I hover on "Product Item Preview"
    When I click "Product Item Gallery Trigger"
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I click "Popup Gallery Widget Close"
    Then I should not see an "Popup Gallery Widget" element

  Scenario: Default state - "Enable Image Preview On Product Listing" is On (tablet view)
    Given I set window size to 992x1024
    And I should see an "Product Item Gallery Trigger" element
    When I click "Product Item Gallery Trigger"
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    When I click "Popup Gallery Widget Close"
    Then I should not see an "Popup Gallery Widget" element
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    Then I should not see an "Product Item Gallery Trigger" element
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    Then I should see an "Product Item Gallery Trigger" element
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    And I click on "ProductFrontendSelectRowCell"
    And I click on "ProductFrontendMassClosePanel"
    Then I should see an "Product Item Gallery Trigger" element

  Scenario: Check that alt attribute in product image is localized
    Given I click "Localization Switcher"
    And I select "Localization 1" localization
    And I should see preview image with alt "Product1 (Localization 1)" for "PSKU1" product
    And I hover on "Product Item Preview"
    When I click "Product Item Gallery Trigger"
    Then I should see gallery image with alt "Product1 (Localization 1)"

  Scenario: "Enable Image Preview On Product Listing" is Off
    Given I proceed as the Admin
    When go to System/ Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And fill "Product Images Form" with:
    | Product Images Default |false |
    | Product Images         |false |
    And submit form
    And I proceed as the User
    And I reload the page
    Then I should see picture for "PSKU1" product in the "ProductFrontendGrid"
    When I hover on "Product Item Preview"
    Then I should not see an "Product Item Gallery Trigger" element
