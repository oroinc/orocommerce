@ticket-BB-13978
@ticket-BB-16275
@feature-BAP-19790
@fixture-OroProductBundle:top_selling_items_block_localization.yml
@regression

Feature: Top Selling Items Block Localization
  In order to have localized product names in "Top Selling Items" Block
  As a User
  I need to be able to see localized product names on "Top Selling Items" block, Shopping Lists widget, alt attributes of products preview images and gallery images.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    # Load image to product to make it show in Top Selling Items Block
    And I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I click Edit SKU1 in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click "Choose Image"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
    # Enable localizations
    And I enable the existing localizations

  Scenario: Check that product name is displayed properly
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    Then should see the following products in the "Top Selling Items Block":
      | Title                  |
      | Product1`"'&йёщ®&reg;> |

  Scenario: Check that alt attributes contain proper product name
    Given I open product gallery for "SKU1" product
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I should see picture "Popup Gallery Widget Picture" element
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product1`\"'&йёщ®&reg;>" for "SKU1" product
    And I should see picture for "SKU1" product in the "Top Selling Items Block"

  Scenario: Check that product name is displayed properly in shopping lists widget
    When click "Add to Shopping List" for "SKU1" product
    And click "In Shopping List" for "SKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Check that product name is localized
    When I click "Localization Switcher"
    And I select "Localization 1" localization
    Then should see the following products in the "Top Selling Items Block":
      | Title                     |
      | Product1 (Localization 1) |

  Scenario: Check that alt attributes are localized
    Given I open product gallery for "SKU1" product
    Then I should see gallery image with alt "Product1 (Localization 1)"
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product1 (Localization 1)" for "SKU1" product

  Scenario: Check that product name is localized in shopping lists widget
    When I click "In Shopping List" for "SKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1 (Localization 1) |
