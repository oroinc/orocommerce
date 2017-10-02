@feature-BB-9570
@fixture-OroOrderBundle:previously-purchased.yml
Feature: Previously purchased products
  In order to quickly re-order goods I have bought recently
  As a customer
  I want to see a list of previously purchased products

  Time period to select purchases from (90 days default) – per website
  Commerce > Orders > Purchase history (new page in the config)
  Option label: Display products purchased within (days)

#Preconditions:
#  Bunch of products:
#  | Product1 | SKU1 |
#  | Product2 | SKU2 |

#  Category: ProductCategory
#
#  management console user: admin
#  front store user: AmandaRCole, Role: Administrator, Customer Group: All Customers, Customer: Customer A
#
#  Shopping list1 with Product1
#  Shopping list2 with Product2
#
 Scenario: Create different window session
   Given sessions active:
     | Admin  |first_session |
     | Buyer  |second_session|


  Scenario: Previously purchased products page is present in Account menu
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I click "Account"
    And I click "Previously Purchased"
    Then page has "Previously Purchased" header
    And I should see "My Account / Previously Purchased"
    Then I should see "No records found"
    And I should see "Recency (Newest first)"

  Scenario: Time restrictions option is present in the Management console and it is 90 days by default
    Given I operate as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    Then the "Display products purchased within" field should contain "90"

  @not-automated
  Scenario: Ordered products are displayed in Previously Purchased page and Recency sort order
    Given I proceed as the Buyer
    When I hover on "Shopping Cart"
    And click "Shopping list1"
    And click "Create Order"
    # TODO: find a shorter way to create an order
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I hover on "Shopping Cart"
    And click "Shopping list2"
    And click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should see "Recency" sort order
    And I should see grid
    | Prodcut2 |
    | Prodcut1 |
    And I select "SKU" in sort order dropdown
    And I select "Recency" in sort order dropdown
    Then I should see grid
      | Prodcut2 |
      | Prodcut1 |

  @not-automated
#    TODO: Discuss with Denys, should we add edge cases in Behat what if we move them to Functional or Unit tests
  Scenario: Time restriction field validators
    Given I proceed as the Admin
    And go to Sales / Orders / Purchase history
    And I fill "Display products purchased within" with ""
    Then I should see validation message ""
    And I fill "Display products purchased within" with "a"
    Then I should see validation message ""
    And I fill "Display products purchased within" with "0.5"
    Then I should see validation message ""
    And I fill "Display products purchased within" with "0"
    Then I should see validation message ""

  @not-automated
  Scenario: Time restriction changes are applicable
    Given I operate as the Admin
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And I type "10" in "Display products purchased within"
    And I proceed as the Buyer
    And I click "Account"
    And I click "Previously Purchased"
    Then I should not see "Product 1"
    And I should see "Product 2"

  @not-automated
  Scenario: Order created by Admin in Management console is displayed in Previously purchased products
    Given I proceed as the Admin
    When go to Sales / Orders
    And click "Create Order"
    And fill form with:
    | Customer      | Customer A      |
    | Customer User | AmandaRCole     |
    | Product       | Prodcut1        |
    And click "Save"
    And I proceed as the Buyer
    And go to Account / Previously purchased products
    Then I should see "Product1"

  @not-automated
  Scenario: Product was added to Order from management console and should be displayed in "Previously purchased products"
    Given I proceed as the Admin
    And go to Sales / Orders
    And click edit Order1 in grid
    And click "Add Product"
    And select Product2 from dropdown
    And click "Save"
    And I proceed as the Buyer
    And go to Account / Previously purchased products
    Then I should see "Product2"

  @not-automated
  Scenario: Disable already ordered product. Check "Previously purchased products"
    Given I proceed as the Admin
    And go to Products / Products
    And click edit Product1 in grid
    And fill form with:
    | Status | Disabled |
    And click "Save"
    And I proceed as the Buyer
    And go to Account / Previously purchased products
    And I should see "Product1"

  @not-automated
  Scenario: Product from page can be added to shopping list
    Given I am on homepage
    When I go to Account / Previously purchased products
    And I click "Add to Shopping list"
    Then I should see "Product has been added to" flash message

  @not-automated
  Scenario: Order was cancelled by Admin in Management console and should not be displayed in "Previously purchased products"
    Given I proceed as the Admin
    And go to Sales / Orders
    And click view Order in grid
    And click Cancel
    And I proceed as the Buyer
    And I click "Account"
    And I click "Previously Purchased"
    Then I should not see "Product 2"
