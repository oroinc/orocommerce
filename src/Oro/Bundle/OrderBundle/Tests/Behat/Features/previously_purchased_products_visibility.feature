@regression
@feature-BB-9570
@fixture-OroOrderBundle:previously-purchased-visibility.yml
Feature: Previously purchased products visibility
  As a store owner
  I want Customer Users to see only products that they are allowed to see in the Previously Purchased page

 Scenario: Create different window session
   Given sessions active:
     | Admin     |first_session |
     | Customer  |second_session|

  Scenario: Previously purchased products feature is enabled and Product visibility: Visibility to All: Visible
    Given I operate as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And fill "Purchase History Settings Form" with:
      | Enable Purchase History Use Default | false |
      | Enable Purchase History             | true  |
    And I save setting
    And I proceed as the Customer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I follow "Account"
    And I click "Previously Purchased"
    Then I should see "Product 1"

  Scenario: Product visibility: Visibility to All: Hidden
    Given I proceed as the Admin
    When go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I should see that option "Category" is selected in "Visibility Product To Customer First Group" select
    And I fill "Visibility Product Form" with:
      | Visibility To Customer First Group | Current Product |
      | Visibility To Customers First      | Current Product |
    And I save and close form
    And I operate as the Customer
    And follow "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to All: Category
    Given I proceed as the Admin
    And go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Category" from "Visibility to All"
    And I save and close form
    And I go to Products / Master Catalog
    And click "Lighting Products"
    And click "Visibility to All"
    And I select "Hidden" from "Visibility to All"
    And I save form
    And I proceed as the Customer
    And follow "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to All: Config
    Given I proceed as the Admin
    When go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Config" from "Visibility to All"
    And I save and close form
    And go to System / Configuration
    And follow "Commerce/Customer/Visibility" on configuration sidebar
    And fill "Visibility Settings Form" with:
      |Product Visibility Use Default |false  |
      |Product Visibility             |hidden |
      |Category Visibility Use Default|false  |
      |Category Visibility            |hidden |
    And I save setting
    And I proceed as the Customer
    And follow "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to Customer Groups: Hidden
    Given I proceed as the Admin
    And go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Visible" from "Visibility to All"
    And I fill "Visibility Product Form" with:
      | Visibility To Customer First Group | Hidden          |
      | Visibility To Customers First      | Customer Group  |
    And I save and close form
    And I proceed as the Customer
    And follow "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to Customers: Hidden
    Given I proceed as the Admin
    And go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I fill "Visibility Product Form" with:
      | Visibility To Customer First Group | Visible |
      | Visibility To Customers First      | Hidden  |
    And I save and close form
    And I proceed as the Customer
    And follow "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"
