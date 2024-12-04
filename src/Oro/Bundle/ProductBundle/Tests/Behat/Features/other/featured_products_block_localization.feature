@ticket-BB-13978
@ticket-BB-16275
@feature-BAP-19790
@fixture-OroProductBundle:featured_products_block_localization.yml
@regression

Feature: Featured Products Block Localization
  In order to have localized product names in "Featured Products" Block
  As a Buyer
  I need to be able to see localized product names on "Featured Products" block, Shopping Lists widget, alt attributes of products preview images and gallery images.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    # Load image to product
    And I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I click Edit SKU1 in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
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
    And I login as AmandaRCole@example.org buyer
    Then should see the following products in the "Featured Products Block":
      | Title                  |
      | Product1`"'&йёщ®&reg;> |

  Scenario: Check that alt attributes contain proper product name
    Given I open product gallery for "SKU1" product
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I should see picture "Popup Gallery Widget Picture" element
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product1`\"'&йёщ®&reg;>" for "SKU1" product
    And I should see picture for "SKU1" product in the "Featured Products Block"

  Scenario: Check the search autocomplete when products found
    When I type "SKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "SKU1" in the "Search Autocomplete Highlight" element
    And I should see an "Search Autocomplete Product Image" element
    And I should see picture "Search Autocomplete Product Picture" element

  Scenario: Check that product name is displayed properly in shopping lists widget
    Given click "Add to Shopping List" for "SKU1" product
    When click "In Shopping List" for "SKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Check that product name is localized
    When I click "Localization Switcher"
    And I select "Localization 1" localization
    Then should see the following products in the "Featured Products Block":
      | Title                     |
      | Product1 (Localization 1) |

  Scenario: Check that alt attributes are localized
    Given I open product gallery for "SKU1" product
    Then I should see gallery image with alt "Product1 (Localization 1)"
    When I click "Popup Gallery Widget Close"
    Then I should see preview image with alt "Product1 (Localization 1)" for "SKU1" product

  Scenario: Check that product name is localized in shopping lists widget
    When click "In Shopping List" for "SKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1 (Localization 1) |
