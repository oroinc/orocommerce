@feature-BB-9570
@fixture-OroOrderBundle:previously-purchased-visibility.yml
@skip This test is skipped due to the bugs BB-10035 and BB-10034
Feature: Previously purchased products visibility

#  TODO: Write feature description

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | Customer  |second_session|

  Scenario: Product visibility: Visibility to All: Visible
    Given I operate as the Customer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I click "Account"
    And I click "Previously Purchased"
    Then I should see "Product 1"

  Scenario: Product visibility: Visibility to All: Hidden
    Given I proceed as the Admin
    And I login as administrator
    When go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    And I operate as the Customer
    And click "Account"
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
    And click "Account"
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
      |Product Visibility Use Default |false     |
      |Product Visibility             |hidden    |
    And I save setting
    And I proceed as the Customer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to Customer Groups: Hidden
    Given I proceed as the Admin
    And go to System / Configuration
    And follow "Commerce/Customer/Visibility" on configuration sidebar
    And fill "Visibility Settings Form" with:
      |Product Visibility Use Default |false     |
      |Product Visibility             |visible   |
    And I save setting
    And go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Visible" from "Visibility to All"
    And I fill "Product Form" with:
      | Visibility To Customer First Group | Hidden |
    And I save and close form
    And I proceed as the Customer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"

  Scenario: Product visibility: Visibility to Customers: Hidden
    Given I proceed as the Admin
    And go to Products / Products
    And click "View" on row "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I fill "Product Form" with:
      | Visibility To Customer First Group | Visible |
      | Visibility To Customers First      | Hidden  |
    And I save and close form
    And I proceed as the Customer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 1"
