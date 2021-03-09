@regression
@ticket-BB-20042
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroProductBundle:products_frontend_search_grid.yml

Feature: Product view page for disabled product
  In order to display 404 not found error on disable product page
  As an Administrator
  I should disable a product
  As an Buyer
  I should see 404 not found error on the product page

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: View product page when product is enabled by unauthorized buyer
    Given I operate as the Buyer
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "Item #: PSKU1"

  Scenario: View product page when product is enabled by authorized buyer
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "Item #: PSKU1"

  Scenario: View product page when product is disabled
    Given I proceed as the Admin
    When I login as administrator
    And go to Products/ Products
    And filter SKU as is equal to "PSKU1"
    And I click Edit "PSKU1" in grid
    And fill form with:
      | Status | Disabled |
    And save and close form
    Then I should see "Product has been saved" flash message

    Given I operate as the Buyer
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "404 Not Found"
    When I click "Sign Out"
    And I open product with sku "PSKU1" on the store frontend
    Then I should see "404 Not Found"
