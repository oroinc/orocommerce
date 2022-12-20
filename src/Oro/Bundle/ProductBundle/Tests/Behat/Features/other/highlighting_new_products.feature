@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:highlighting_new_products.yml
@regression
Feature: Highlighting new products
  In order to highlight selected new products
  As an Administrator
  I want to be able to mark certain products to be displayed as new on the store frontend

#  Description
#  Add a new product field "New Arrival" into the "General" section:
#  Type - Yes/No drop-down
#  Default value - "No"
#  Hint -
#  When set to "Yes" the product will be highlighted on the store frontend.
#  Modify the default frontend theme to highlight the new products (see the design below):
#  on grid views with images - show an additional stamp "New" on the product image
#  on the no-image-view - show "New Arrival" label below the "Item #: 1234" string
#  Add "New Arrival" column (optional, hidden by default) to the Products -> Products grid in the backoffice, enable inline editing for this column.
#  Add "New Arrival" filter (optional, hidden by default) to the Products -> Products grid in the backoffice.
#  Include the "New Arrival" fiedl into product export/import.
#  Please, also include the "Is Featured" field into product export/import.
#  Configuration
#  No configuration necessary.
#  Acceptance Criteria
#  Demonstrate how the user can mark some products as new arrivals and that they become highlighted on the store frontend in product listings in category and in search results
#  Demonstrate different types of highlighint in different grid views
#  Show that new arrivals can be included into a web-catalog product collection (see demo data below).
#  Sample Data
#  Modify the "New Arrivals" sub-nodes to be product collections which should include products marked as new arrivals in corresponsind categories.
#  Modify the product data so that 3-4 products within each of the categories are marked as new arrivals.

  Scenario: Create different window session
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|

  Scenario: Assign product
    Given I proceed as the Admin
    And login as administrator
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When fill form with:
    |New Arrival|Yes|
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: New Arrival Sticker for a not logged in user
    Given I proceed as the User
    And I am on the homepage
    When I click "NewCategory"
    Then should see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Sticker" for "PSKU2" product
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then should see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Sticker" for "PSKU2" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    And should see "New Arrival Text" for "PSKU1" product
    And should not see "New Arrival Text" for "PSKU2" product

  Scenario: New Arrival Sticker for logged in user
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When I click "Catalog Switcher Toggle"
    And I click "List View"
    Then should see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Sticker" for "PSKU2" product
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then should see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Sticker" for "PSKU2" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    And should see "New Arrival Text" for "PSKU1" product
    And should not see "New Arrival Text" for "PSKU2" product

  Scenario: New Arrival Sticker on product view page - active
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Promotions" on configuration sidebar
    When fill "Promotions Form" with:
      |Show On Product View Default|false|
      |Show On Product View        |Yes  |
    And submit form
    Then I should see "Configuration saved" flash message
    And I proceed as the User
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    When click "View Details" for "PSKU1" product
    Then I should see an "New Arrival Sticker" element
    And click "Sign Out"
    And click "NewCategory"
    When click "View Details" for "PSKU1" product
    Then I should see an "New Arrival Sticker" element

  Scenario: New Arrival Sticker on product view page - not active
    Given I proceed as the Admin
    When fill "Promotions form" with:
      |Show On Product View Default|true|
    And submit form
    Then I should see "Configuration saved" flash message
    And I proceed as the User
    When reload the page
    Then I should not see an "New Arrival Sticker" element
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "NewCategory"
    When click "View Details" for "PSKU1" product
    Then I should not see an "New Arrival Sticker" element
    And click "Sign Out"

  Scenario: UnAssign product
    Given I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When fill form with:
      |New Arrival|No|
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: New Arrival Sticker for not loged user (UnAssign)
    Given I proceed as the User
    And I am on the homepage
    When I click "NewCategory"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Text" for "PSKU1" product

  Scenario: New Arrival Sticker for loged user (UnAssign)
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When I click "Catalog Switcher Toggle"
    And I click "List View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then should not see "New Arrival Sticker" for "PSKU1" product
    And should not see "New Arrival Text" for "PSKU1" product
