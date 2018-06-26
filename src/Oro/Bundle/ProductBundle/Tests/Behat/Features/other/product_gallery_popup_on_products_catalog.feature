@ticket-BB-13978
@fixture-OroProductBundle:product_listing_images.yml

Feature: Product Gallery Popup On Products Catalog
  ToDo: BAP-16103 Add missing descriptions to the Behat features
    
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
            | File     | Main  | Listing | Additional |
            | cat1.jpg | 1     | 1       | 1          |
            | cat2.jpg |       |         | 1          |
        And I save and close form
        Then I should see "Product has been saved" flash message
        # Enable localizations
        And I enable the existing localizations

    Scenario: Default state - "Enable Image Preview On Product Listing" is On
        Given I proceed as the User
        When I am on the homepage
        And I click "NewCategory"
        And I hover on "Product Item Preview"
        And I should see an "Product Item Gallery Trigger" element
        And click "Product Item Gallery Trigger"
        And I should see an "Popup Gallery Widget" element
        And I click "Popup Gallery Widget Close"
        Then I should not see an "Popup Gallery Widget" element

    Scenario: Check that alt attribute in product image is localized
        Given I press "Localization Switcher"
        And I select "Localization 1" localization
        And I should see preview image with alt "Product1 (Localization 1)" for "PSKU1" product
        And I hover on "Product Item Preview"
        When I click "Product Item Gallery Trigger"
        Then I should see gallery image with alt "Product1 (Localization 1)"

    Scenario: "Enable Image Preview On Product Listing" is Off
        Given I proceed as the Admin
        And go to System/ Configuration
        And I follow "Commerce/Product/Product Images" on configuration sidebar
        And fill "Product Images Form" with:
            | Product Images Default |false |
            | Product Images         |false |
        And submit form
        And I proceed as the User
        And I reload the page
        When I hover on "Product Item Preview"
        Then I should not see an "Product Item Gallery Trigger" element
