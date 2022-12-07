@feature-BB-8715
@ticket-BB-13978
@ticket-BB-16275
@feature-BAP-19790
@fixture-OroProductBundle:showing_related_items_products.yml
@fixture-OroProductBundle:related_items_system_users.yml
@fixture-OroProductBundle:related_items_customer_users.yml
@regression

Feature: Showing upsell products
  In order to be offer the customer to buy some products in addition to the one that he is looking at
  As an Administrator
  I want to the "Upsell Products" block displayed on the product view page
  I need to be able to see localized product names on "Up-sell Products" block, Shopping Lists widget, alt attributes of products preview images and gallery images.

  Scenario: Create two session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I proceed as the Admin
    And I login as administrator
    # Load image to product
    And I proceed as the Admin
    And go to Products/ Products
    And I click Edit "PSKU2" in grid
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

  Scenario: Verify that "Up-sell Products" block is not displayed if product doesn't have upsell items
    Given I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    Then I should not see "Up-sell Products"

  Scenario: Minimum Items restriction
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Maximum Items Use Default | false |
      | Maximum Items             | 6     |
      | Minimum Items Use Default | false |
      | Minimum Items             | 4     |
    And I fill "SimilarProductsConfig" with:
      | Enable Similar Products Use Default | false |
      | Enable Similar Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |
    And I click "Select products"
    And I save and close form
    When I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    Then I should not see "Up-sell Products"

  Scenario: Maximum Items restriction
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Maximum Items Use Default | false |
      | Maximum Items             | 2     |
      | Minimum Items Use Default | false |
      | Minimum Items             | 1     |
    And I click "Save settings"
    When I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    Then I should see "Up-sell Products"
    And I should see "PSKU2"
    And I should see "PSKU3"
    And I should not see "PSKU4"

  Scenario: Verify equivalence partitioning for Minimum and Maximum Items
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Maximum Items Use Default | false |
      | Maximum Items             | 2     |
      | Minimum Items Use Default | false |
      | Minimum Items             | 2     |
    And I click "Save settings"
    When I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    Then I should see "Up-sell Products"
    And I should see "PSKU2"
    And I should see "PSKU3"
    And I should not see "PSKU4"

  Scenario: Disabled products are not displayed in "Up-sell Products" block
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Maximum Items Use Default | false |
      | Maximum Items             | 6     |
      | Minimum Items Use Default | false |
      | Minimum Items             | 1     |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU3" in grid
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU2 |
      | PSKU4 |
      | PSKU5 |
    And I click "Select products"
    And I save and close form
    When I proceed as the Buyer
    And type "PSKU3" in "search"
    And click "Search Button"
    And I should see "PSKU3" product
    And I click "View Details" for "PSKU3" product
    Then I should see "Up-sell Products"
    And I should see "PSKU2"
    And I should see "PSKU4"
    And I should not see "PSKU5"

  Scenario: Check that product name is localized and displayed properly
    Given I click "Localization Switcher"
    When I select "Localization 1" localization
    When type "PSKU1" in "search"
    And click "Search Button"
    Then I should see "PSKU1" product
    When I click "View Details" for "PSKU1" product
    Then should see the following products in the "Upsell Products Block":
      | Title                               |
      | Product2Localization1`"'&йёщ®&reg;> |

  Scenario: Check that alt attributes are localized and displayed properly
    Given I open product gallery for "PSKU2" product
    Then I should see gallery image with alt "Product2Localization1`\"'&йёщ®&reg;>"
    And I should see picture "Popup Gallery Widget Picture" element
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product2Localization1`\"'&йёщ®&reg;>" for "PSKU2" product
    And I should see picture for "PSKU2" product in the "Upsell Products Block"

  Scenario: Check that product name is localized and displayed properly in shopping lists widget
    When I click "Add to Shopping List" for "PSKU2" product
    And I click "In Shopping List" for "PSKU2" product
    Then I should see "UiDialog" with elements:
      | Title | Product2Localization1`"'&йёщ®&reg;> |
    And I close ui dialog

#  Scenario: Check up-sell items are displayed as slider when "use slider on mobile" option is checked
#  TODO: Fix this check when we will be able to emulate mobile
#    Given I proceed as the Buyer
#    And type "PSKU1" in "search"
#    And click "Search Button"
#    And I should see "PSKU1" product
#    And I click "Product 1"
#    And I should see "Up-sell Products"
#    And I should see an "Up-sell Products Slider" element
#    And I set window size to 400x999
#    And I reload the page
#    And I should not see an "Up-sell Products Slider" element
#    And I set window size to 1920x1280
#    And I proceed as the Admin
#    And go to System/ Configuration
#    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
#    And I fill "UpsellProductsConfig" with:
#      | Use Slider On Mobile Use Default | false |
#      | Use Slider On Mobile             | true |
#    And I click "Save settings"
#    And I proceed as the Buyer
#    And type "PSKU1" in "search"
#    And click "Search Button"
#    And I should see "PSKU1" product
#    And I click "Product 1"
#    And I should see "Up-sell Products"
#    And I should see an "Up-sell Products Slider" element
#    And I set window size to 400x999
#    And I reload the page
#    Then I should see an "Up-sell Products Slider" element

  Scenario: Verify that "Up-sell Products" block is displayed in "Short page" layout view
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And I fill "Page Templates Form" with:
      | Use Default  | false      |
      | Product Page | Short page |
    And I click "Save settings"
    And I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    When I click "View Details" for "PSKU1" product
    Then I should see "Up-sell Products"
    Then should see the following products in the "Upsell Products Block":
      | Title                               |
      | Product2Localization1`"'&йёщ®&reg;> |
    When click "In Shopping List" for "PSKU2" product
    Then I should see "UiDialog" with elements:
      | Title | Product2Localization1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Verify that "Up-sell Products" block is displayed in "Two columns page" layout view
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And I fill "Page Templates Form" with:
      | Use Default  | false            |
      | Product Page | Two columns page |
    And I click "Save settings"
    And I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    When I click "View Details" for "PSKU1" product
    Then I should see "Up-sell Products"
    Then should see the following products in the "Upsell Products Block":
      | Title                               |
      | Product2Localization1`"'&йёщ®&reg;> |
    When click "In Shopping List" for "PSKU2" product
    Then I should see "UiDialog" with elements:
      | Title | Product2Localization1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Verify that "Up-sell Products" block is displayed in "List page" layout view
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And I fill "Page Templates Form" with:
      | Use Default  | false     |
      | Product Page | List page |
    And I click "Save settings"
    And I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    When I click "View Details" for "PSKU1" product
    Then I should see "Up-sell Products"
    Then should see the following products in the "Upsell Products Block":
      | Title                               |
      | Product2Localization1`"'&йёщ®&reg;> |
    When click "In Shopping List" for "PSKU2" product
    Then I should see "UiDialog" with elements:
      | Title | Product2Localization1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: "Add to Shopping List" button restrictions
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Show Add Button Use Default | false |
      | Show Add Button             | false |
    And I click "Save settings"
    And I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    And I should see "Up-sell Products"
    Then I should not see "Add to Shopping List" in upsell products

  Scenario: Check if Related block is not displayed in case when Upsell feature is enabled but Related is disabled.
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | true  |
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    And I proceed as the Buyer
    And type "PSKU1" in "search"
    And click "Search Button"
    And I should see "PSKU1" product
    And I click "View Details" for "PSKU1" product
    And I should see "Up-sell Products"
    And I should not see "Related Products"

  Scenario: Check if Related TAB is not displayed in case when Upsell feature is enabled but Related is disabled.
    Given I proceed as the Admin
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should see "Up-sell Products"
    And I should not see "Related Products"
