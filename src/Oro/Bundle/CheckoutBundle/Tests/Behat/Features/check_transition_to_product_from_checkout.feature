@ticket-BB-20355
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Check transition to product from checkout
  In order to follow to a product from checkout on front store
  As a buyer
  I want to be able to follow the product from checkout when I click on product link

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    When I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I click Edit SKU123 in grid
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

  Scenario: Check possibility to go to the product from checkout
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click on "Checkout Line Item Product Link"
    Then I should see "All Products / 400-Watt Bulb Work Light"

  Scenario: Check possibility to go to the product from single page checkout
    Given I proceed as the Admin
    When I go to System / Workflows
    And I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message
    When I proceed as the Buyer
    And I open page with shopping list List 1
    And I click "Create Order"
    And I click on "Checkout Line Item Product Link"
    Then I should see "All Products / 400-Watt Bulb Work Light"
