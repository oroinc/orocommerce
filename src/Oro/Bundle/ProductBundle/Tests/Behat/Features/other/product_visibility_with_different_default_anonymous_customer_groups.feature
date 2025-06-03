@ticket-BB-25629
@regression

Feature: Product visibility with different default anonymous customer groups
  As an administrator, I want to be able to change the default anonymous customer group in the system configuration.
  Check the visibility of the products in the storefront by changing the default anonymous customer group and
  setting different visibility rules for the groups.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Create new customer group for default organization
    Given I proceed as the Admin
    And login as administrator
    And go to Customers / Customer Groups
    And click "Create Customer Group"
    When I fill "Customer Group Form" with:
      | Name | Partners |
    And save and close form
    Then should see "Customer group has been saved" flash message

  Scenario: Create a simple product
    Given I go to Products/ Products
    And click "Create Product"
    When I fill form with:
      | Type | Simple |
    And click "All Products"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | PSKU   |
      | Name             | Item   |
      | Status           | Enable |
      | Unit Of Quantity | item   |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create web catalog and root content node
    Given I go to Marketing / Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Default Web Catalog |
    When I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    When I click "Edit Content Tree"
    And fill "Content Node Form" with:
      | Titles | Home page |
    And click on "Show Variants Dropdown"
    And click "Add Landing Page"
    And fill "Content Node Form" with:
      | Titles       | Root Node |
      | Landing Page | Homepage  |
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create product content node and assign product
    Given I click "Create Content Node"
    When I fill "Content Node Form" with:
      | Titles   | Product Node |
      | Url Slug | product-node |
    And click on "Show Variants Dropdown"
    And click "Add Category"
    And click "All Products"
    And click "Save"
    Then I should see "Content Node has been saved" flash message
    And I set "Default Web Catalog" as default web catalog

  Scenario: Guest sees product node and product on storefront
    Given I proceed as the Guest
    When I am on the homepage
    And click on "Main Menu Button"
    Then Main menu should contain "/product-node" with "Product Node"
    When I click "Product Node" in hamburger menu
    Then number of records in "Product Frontend Grid" should be 1
    And should see "PSKU" product

  Scenario: Hide product from non-authenticated visitors
    Given I proceed as the Admin
    And go to Products / Products
    And click "View" on row "PSKU" in grid
    And click "More actions"
    And click "Manage Visibility"
    When I fill "Visibility Product Form" with:
      | Non-Authenticated Visitors Customer Group | Hidden |
    And I save and close form

  Scenario: Guest no longer sees hidden product
    Given I proceed as the Guest
    And I reload the page
    Then number of records in "Product Frontend Grid" should be 0
    And I should not see "PSKU" product

  Scenario: Restrict product content node for customer group Partners
    Given I proceed as the Admin
    And go to Marketing / Web Catalogs
    And click view Default Web Catalog in grid
    And click "Edit Content Tree"
    And click "Product Node"
    When I uncheck "Inherit Parent" element
    And fill "Content Node Form" with:
      | Content Node Restrictions Customer Group | Partners |
    And click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Guest no longer sees content node in menu
    Given I proceed as the Guest
    And I am on homepage
    When I click on "Main Menu Button"
    Then Main menu should not contain "/product-node"

  Scenario: Assign Partners group as default anonymous group
    Given I proceed as the Admin
    And go to System/ User Management/ Organizations
    And click "Configuration" on row "ORO" in grid
    And follow "Commerce/Guest/Website Access" on configuration sidebar
    When I fill "Anonymous Customer Group Access Configuration Form" with:
      | Non-Authenticated Visitors Customer Group | Partners |
    And submit form
    Then I should see "Configuration saved" flash message

  # This is possible because all guests users belong to the Partners customer group, which has no restrictions on the
  # visibility of categories and products
  Scenario: Guest sees restricted product node after changing default group
    Given I proceed as the Guest
    When I am on the homepage
    And I click on "Main Menu Button"
    Then Main menu should contain "/product-node" with "Product Node"
    When I click "Product Node" in hamburger menu
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU" product
