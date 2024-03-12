@behat-test-env
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroUPSBundle:Checkout.yml
@fixture-OroUPSBundle:ProductWithShippingOptions.yml
@fixture-OroUPSBundle:Integration.yml
@fixture-OroUPSBundle:ShippingMethodsConfigsRule.yml
@ticket-BB-22743
@ticket-BB-23311

Feature: UPS shipping cost calculation
  In order to be able to use UPS as a shipping provider
  As a Buyer
  I need to be able to get UPS shipping cost during checkout
  As an Admin
  I need to be able to edit order in backoffice

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check that UPS shipping cost is calculated correctly for simple products
    Given I expect the following shipping costs:
      | Method          | Cost  | Currency |
      | UPS 2nd Day Air | 99.75 | USD      |

  Scenario: Create order with simple products
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then Buyer is on enter billing information checkout step
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I should see "UPS 2nd Day Air: $1,199.75"
    And I check "UPS 2nd Day Air" on the "Shipping Method" checkout step and press Continue
    Then I see next subtotals for "Checkout Step":
      | Subtotal | Amount    |
      | Shipping | $1,199.75 |
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I see next subtotals for "Checkout Step":
      | Subtotal | Amount    |
      | Shipping | $1,199.75 |
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Check order with simple products in backoffice
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/ Orders
    When I click view "1" in grid
    Then I should see "Shipping $1,199.75"

  Scenario: Check that UPS shipping cost is calculated correctly for kit products
    Given I expect the following shipping costs:
      | Method          | Cost  | Currency |
      | UPS 2nd Day Air | 63.75 | USD      |

  Scenario: Check UPS shipping for checkout with kit products
    Given I proceed as the Buyer
    And I open page with shopping list List 2
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then Buyer is on enter billing information checkout step
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    Then I should see "UPS 2nd Day Air: $1,163.75"

  Scenario: Check that UPS shipping cost is calculated correctly when product kit has no valid shipping options
    Given I expect the following shipping costs:
      | Method          | Cost  | Currency |
      | UPS 2nd Day Air | 55.25 | USD      |

  Scenario: Set no valid shipping options for kit product
    Given I proceed as the Admin
    And I go to Products/Products
    When I click Edit KIT001 in grid
    And I click "Remove Product Shipping Options 1"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check UPS shipping for checkout when product kit has no valid shipping options
    Given I proceed as the Buyer
    And I open page with shopping list List 2
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then Buyer is on enter billing information checkout step
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    Then I should see "UPS 2nd Day Air: $1,155.25"

  Scenario: Set no valid shipping options for related simple product
    Given I proceed as the Admin
    And I go to Products/Products
    When I click Edit SKU124 in grid
    And I click "Remove Product Shipping Options 1"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check UPS shipping for checkout when related simple product has no valid shipping options
    Given I proceed as the Buyer
    And I open page with shopping list List 2
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then I should not see "UPS 2nd Day Air"
