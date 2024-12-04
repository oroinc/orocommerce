@ticket-BB-13978
@ticket-BB-16275
@ticket-BAP-19790
@fixture-OroProductBundle:new_arrivals_block.yml
@regression

Feature: New Arrivals Block
  In order to promote new arrivals on the store homepage
  As an Administrator
  I need to be able to mark some products as "New Arrivals" and see them in "New Arrivals" block on homepage
  I need to be able to specify the number of items to display and see corresponding changes in "New Arrivals" block on homepage
  I need to be able to change order of items and see corresponding changes in "New Arrivals" block on homepage
  I need to be able to see localized product names on "New Arrivals" block, Shopping Lists widget, alt attributes of products preview images and gallery images.

#  Description
#  Create a segment, called "New Arrivals (Home Page)":
#  Entity - Product
#  Type - Dynamic
#  Limit - 4
#  Columns - ID, Updated At (sorting Desc)
#  Filter by the "New Arrival" field (   BB-9565 IN PROGRESS  )
#  Use this segment as the data source for the "New Arrivals" block on the homepage.
#  Make sure that "New Arrivals" block shows only the products that are visible to the current user.
#  There should be 2 different templates for the mobile devices (with and without slider) - configurable by administrator (see configuration below).
#  Configuration
#  Add new setting to the page System -> Configuration -> COMMERCE -> Product -> Promotions:
#  New Arrivals
#  Product Segment: drop-down with segment selector (filter values by entity type = Product)
#  Maximum Items: input field, default value = 4, hint:
#  Show not more than the specified number of items (additionaly limits the list of items retrieved from the selected segment after filtering out the products that are not visible to the current user).
#  Minimum Items: input field, default value = 3. hint:
#  Hide the "News Arrivals" block completely if the number of items is less than the specified value.
#  Use Slider On Mobile: checkbox, devault value - false (unselected), hint:
#  When slider is enabled, the "New Arrivals" block will occupy less screen space, while showing larger product images.
#  These settings should be configurable on the global, organization and website levels.
#  Acceptance Criteria
#  Demonstrate how products marked as new arrivals appear in the "New Arrivals" block on the store homepage
#  Demonstrate how an administrator can modify the filter which determines what products to include, modify the sort order and change the number of items in the block
#  Demonstrate how after the modification of product visibility to customer groups or customers, the no longer visible products are replace with some other products in the "New Arrivals" block
#  Configure maximum and minimum number of products to be displayed to 3 and show that the block is displayed when there are only 3 products and show that the block disappears completely when there are less than 3 products
#  Show that slider is present and works when there are more products than it is possible to fit on the page

  Scenario: Create different window session
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|
    # Load image to product
    And I proceed as the Admin
    And login as administrator
    And go to Products/ Products
    And I click Edit "SKU6" in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    When I save and close form
    Then I should see "Product has been saved" flash message
    # Enable localizations
    And I enable the existing localizations

  Scenario: Default state - "New Arrival" on and New Arrival segment selected
    Given I proceed as the User
    When I am on the homepage
    Then should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU6|
      |SKU7|
    And should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|
    When I signed in as AmandaRCole@example.org on the store frontend
    Then should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU6|
      |SKU7|
    And should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|

  Scenario: New Arrival is off
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Promotions" on configuration sidebar
    And fill "Promotions Form" with:
      |Product Segment Default|false         |
      |Product Segment        |Choose segment|
    And submit form
    When I proceed as the User
    And reload the page
    Then I should not see "New Arrivals"
    When click "Sign Out"
    Then I should not see "New Arrivals"

  Scenario: "New Arrival" on and Featured segment selected
    Given I proceed as the Admin
    And fill "Promotions Form" with:
      |Product Segment Default|false            |
      |Product Segment        |Featured Products|
      |Maximum Items Default  |false            |
      |Maximum Items          |5                |
      |Minimum Items Default  |false            |
      |Minimum Items          |3                |
    And submit form
    And I proceed as the User
    When reload the page
    Then should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|
    And should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU5|
      |SKU6|
      |SKU7|
    And should see "New Arrival Sticker" for the following products in the "Featured Products Block":
      |SKU |
      |SKU5|
      |SKU6|
      |SKU7|
    And should not see "New Arrival Sticker" for the following products in the "New Arrivals Block":
      |SKU |
      |SKU5|
      |SKU6|
      |SKU7|
    When I signed in as AmandaRCole@example.org on the store frontend
    Then should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|
      |SKU4|
    And should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU5|
      |SKU6|
      |SKU7|

  Scenario: Minimum Items is set low then the actual
    Given I proceed as the Admin
    And fill "Promotions Form" with:
      |Product Segment Default|true|
      |Maximum Items          |3   |
      |Minimum Items          |4   |
    And submit form
    And I proceed as the User
    When reload the page
    And should not see "New Arrivals"

  Scenario: Maximum Items is set low then the actual
    Given I proceed as the Admin
    And fill "Promotions Form" with:
      |Maximum Items|2|
      |Minimum Items|2|
    And submit form
    And I proceed as the User
    When reload the page
    And should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU6|
      |SKU7|

  Scenario: New Site creation and configuration (Site level)
    Given I proceed as the Admin
    And go to System/ Websites
    And click "Create Website"
    And fill form with:
      |Name                           |NewSite                   |
      |Guest Role                     |Non-Authenticated Visitors|
      |Default Self-Registration Role |Buyer                     |
    And save and close form
    And should see "Website has been saved" flash message
    And go to System/ Websites
    And click "Set default" on row "NewSite" in grid
    And click "Configuration" on row "Default" in grid
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing General form" with:
    |URL Use System       |false                            |
    |URL                  |http://non-existing-url.local    |
    |Secure URL Use System|false                            |
    |Secure URL           |http://non-existing-url.local    |
    And submit form
    And I should see "Configuration saved" flash message
    And go to System/ Websites
    And click "Configuration" on row "NewSite" in grid

  Scenario: "New Arrival" on and "New Arrivals" segment selected (Site level)
    Given I proceed as the Admin
    And I follow "Commerce/Product/Promotions" on configuration sidebar
    And fill "Promotions Form" with:
      |Product Segment Default|false            |
      |Product Segment        |New Arrivals     |
      |Maximum Items Default  |false            |
      |Maximum Items          |4                |
      |Minimum Items Default  |false            |
      |Minimum Items          |3                |
    And submit form
    And I proceed as the User
    When reload the page
    Then should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU4|
      |SKU5|
      |SKU6|
      |SKU7|
    And should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|
    And should see "New Arrival Sticker" for the following products in the "Featured Products Block":
      |SKU |
      |SKU5|
      |SKU6|
      |SKU7|
    And should not see "New Arrival Sticker" for the following products in the "New Arrivals Block":
      |SKU |
      |SKU4|
      |SKU5|
      |SKU6|
      |SKU7|
    When I signed in as AmandaRCole@example.org on the store frontend
    Then should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU4|
      |SKU5|
      |SKU6|
      |SKU7|
    And should not see the following products in the "New Arrivals Block":
      |SKU |
      |SKU1|
      |SKU2|
      |SKU3|

  Scenario: Check that nothing is changed on default website (Site level)
    When I proceed as the Admin
    And go to System/ Websites
    And click "Set default" on row "Default" in grid
    And click "Configuration" on row "Default" in grid
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing General form" with:
      |URL Use System       |true|
      |Secure URL Use System|true|
    And submit form
    And I should see "Configuration saved" flash message
    And go to System/ Websites
    And click "Configuration" on row "NewSite" in grid
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing General form" with:
      |URL Use System       |false                            |
      |URL                  |http://non-existing-url.local    |
      |Secure URL Use System|false                            |
      |Secure URL           |http://non-existing-url.local    |
    And submit form
    And I should see "Configuration saved" flash message
    And I proceed as the User
    When reload the page
    And should see the following products in the "New Arrivals Block":
      |SKU |
      |SKU6|
      |SKU7|

  Scenario: Check that product name is displayed properly
    Given I proceed as the User
    Then should see the following products in the "New Arrivals Block":
      | Title                  |
      | Product6`"'&йёщ®&reg;> |

  Scenario: Check that alt attributes contain proper product name
    Given I open product gallery for "SKU6" product
    Then I should see gallery image with alt "Product6`\"'&йёщ®&reg;>"
    And I should see picture "Popup Gallery Widget Picture" element
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product6`\"'&йёщ®&reg;>" for "SKU6" product
    And I should see picture for "SKU6" product in the "New Arrivals Block"

  Scenario: Check that product name is localized in shopping lists widget
    When I click "Add to Shopping List" for "SKU6" product
    And click "In Shopping List" for "SKU6" product
    Then I should see "UiDialog" with elements:
      | Title | Product6`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Check that product name is localized
    When I click "Localization Switcher"
    And I select "Localization 1" localization
    Then should see the following products in the "New Arrivals Block":
      | Title                     |
      | Product6 (Localization 1) |

  Scenario: Check that alt attributes are localized
    Given I open product gallery for "SKU6" product
    Then I should see gallery image with alt "Product6 (Localization 1)"
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product6 (Localization 1)" for "SKU6" product

  Scenario: Check that product name is localized in shopping lists widget
    When I click "In Shopping List" for "SKU6" product
    Then I should see "UiDialog" with elements:
      | Title | Product6 (Localization 1) |
