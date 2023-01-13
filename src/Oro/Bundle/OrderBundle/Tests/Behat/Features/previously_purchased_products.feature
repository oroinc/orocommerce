@regression
@random-failed
@feature-BB-9570
@fixture-OroOrderBundle:previously-purchased.yml
Feature: Previously purchased products
  In order to quickly re-order goods I have bought recently
  As a customer
  I want to see a list of previously purchased products

  Time period to select purchases from (90 days default) – per website
  Commerce > Orders > Purchase history (new page in the config)
  Option label: Display products purchased within (days)

 Scenario: Create different window session
   Given sessions active:
     | Admin  |first_session |
     | Buyer  |second_session|

  Scenario: Time restrictions option is present in the Management console and it is 90 days by default
    Given I operate as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And fill "Purchase History Settings Form" with:
      | Enable Purchase History Use Default | false |
      | Enable Purchase History             | true  |
    And I save setting
    Then the "Display products purchased within" field should contain "90"

  Scenario: Previously purchased products page is present in Account menu
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I follow "Account"
    And I click "Previously Purchased"
    Then page has "Previously Purchased" header
    And I should see "My Account / Previously Purchased"
    Then I should see "Product 1"
    And I should see "Product 2"
    And I should see "Recency (Newest first)"

  Scenario: Check all sorting options available and Relevance option (Please select) is not visible
    Then I should not see "Relevance" in the "Frontend Product Grid Sorter" element
    Then I should see next options in "Frontend Product Grid Sorter"
      |Recency (Oldest first)|
      |Recency (Newest first)|
      |Name (Low to High)    |
      |Name (High to Low)    |
      |Price (Low to High)   |
      |Price (High to Low)   |

  Scenario: Product from page can be added to shopping list
    Given I operate as the Buyer
    When I follow "Account"
    And I click "Previously Purchased"
    And I click "Add to Shopping List" for "PSKU2" product
    Then I should see "Product has been added to" flash message and I close it

  Scenario: Time restriction changes are applicable
    Given I operate as the Admin
    And there is a feature "previously_purchased_products" enabled
    And there is an order "OldOrder" created "-15 days"
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And fill "Purchase History Settings Form" with:
      | Purchased Within Use Default | false |
      | Purchased Within             | 10    |
    And I save setting
    And I proceed as the Buyer
    And I follow "Account"
    And I click "Previously Purchased"
    Then I should not see "Product 1"
    And I should see "Product 2"

  Scenario: Order created by Admin in Management console is displayed in Previously purchased products
    Given I proceed as the Admin
    When go to Sales / Orders
    And click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer         | first customer                               |
      | Customer User    | Amanda Cole                                  |
      | Billing Address  | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Product          | PSKU4                                        |
      | Quantity         | 5                                            |
      | Price            | 10                                           |
    And click "Calculate Shipping Button"
    And I save and close form
    And I proceed as the Buyer
    And I follow "Account"
    And I click "Previously Purchased"
    Then I should see "Product 4"

  Scenario: Product was added to Order from management console and should be displayed in "Previously purchased products"
    Given I proceed as the Admin
    And go to Sales / Orders
    And click Edit "SimpleOrder" in grid
    And click "Add Product"
    And fill "Order Form" with:
      | Product2  | PSKU3 |
      | Quantity2 | 5     |
      | Price2    | 10    |
    And click "Calculate Shipping Button"
    And I save and close form
    And I proceed as the Buyer
    And I follow "Account"
    And I click "Previously Purchased"
    Then I should see "Product 3"

  Scenario: Disable already ordered product. Check "Previously purchased products"
    Given I proceed as the Admin
    And go to Products / Products
    And click Edit "PSKU3" in grid
    And fill "ProductForm" with:
    | Status | Disabled |
    And I save and close form
    And I proceed as the Buyer
    And I follow "Account"
    And I click "Previously Purchased"
    And reload the page
    And I wait for products to load
    And I should not see "Product 3"
    When I type "Product" in "search"
    And I click "Search Button"
    Then should not see "SKU3"

  Scenario: Order was cancelled by Admin in Management console and should not be displayed in "Previously purchased products"
    Given I proceed as the Admin
    And go to Sales / Orders
    And click View "SimpleOrder" in grid
    And click "Cancel"
    And I proceed as the Buyer
    And I follow "Account"
    And I click "Previously Purchased"
    Then I should not see "Product 2"
