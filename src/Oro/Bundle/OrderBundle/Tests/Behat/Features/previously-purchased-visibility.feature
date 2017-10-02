@feature-BB-9570
@fixture-OroOrderBundle:previously-purchased-visibility.yml
Feature: Previously purchased products visibility

#  TODO: Write feature description

 Scenario: Create different window session
   Given sessions active:
     | Admin  |first_session |
     | Buyer  |second_session|


  Scenario: Product visibility: Visibility to All: Hidden
    Given I proceed as the Admin
    And I login as administrator
    When go to Products / Products
    And click view "Product 2" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    And I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 2"

  Scenario: Product visibility: Visibility to All: Config
    Given I proceed as the Admin
    When go to Products / Products
    And click view "Product 2" in grid
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
    And I proceed as the Buyer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 2"

  @not-automated
#  TODO: This scenario requires specific fixtures
  Scenario: Product visibility: Visibility to All: Category
    Given I proceed as the Admin
    And go to Products / Products
    And click view Product2 in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Category" from "Visibility to All"
    And I go to Products / Master Catalog
    And click "ProductCategory"
    And click "Visibility"
    And I fill "Visibility to all" with:
      | Visibility To All | Hidden |
    And I proceed as the Buyer
    And go to Account / Previously purchased products
    Then I should not see "Product2"


  Scenario: Product visibility: Visibility to Customer Groups: Hidden
    Given I proceed as the Admin
    And go to System / Configuration
    And follow "Commerce/Customer/Visibility" on configuration sidebar
    And fill "Visibility Settings Form" with:
      |Product Visibility Use Default |false     |
      |Product Visibility             |visible   |
    And I save setting
    And go to Products / Products
    And click view "Product 2" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Visible" from "Visibility to All"
    And I fill "Visibility to Customer Groups" with:
      | All Customers | Hidden |
    And I save and close form
    And I proceed as the Buyer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 2"

  @not-automated
  Scenario: Product visibility: Visibility to Customers: Hidden
    Given I proceed as the Admin
    And go to Products / Products
    And click view "Product 2" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I fill "Visibility to Customer Groups" with:
      | All Customers | Visible |
    And I fill "Visibility to Customers" with:
      | Customer A | Hidden |
    And I proceed as the Buyer
    And click "Account"
    And click "Previously Purchased"
    Then I should not see "Product 2"













