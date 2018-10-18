@regression
@ticket-BB-13286
@fixture-OroCheckoutBundle:ReOrder/BaseIntegrationsFixture.yml
@fixture-OroCheckoutBundle:AdditionalIntegrations.yml
@fixture-OroCheckoutBundle:ReOrder/CustomerUserFixture.yml
@fixture-OroCheckoutBundle:ReOrder/CustomerUserAddressFixture.yml
@fixture-OroCheckoutBundle:ReOrder/ProductFixture.yml
@fixture-OroCheckoutBundle:ReOrder/OrderFixture.yml
@fixture-OroCheckoutBundle:ReOrder/PaymentTransactionFixture.yml
@fixture-OroWarehouseBundle:ReOrder/InventoryLevelFixture.yml

Feature: ShippingAddress and shippingOrigin should be usable in payment rules expression
  In order to use shippingAddress and shippingOrigin fields in payment rules
  As a Sales rep
  I want to create shipping rule that will disable Check/Money Order shipping method
  Scenario: Create two sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Checking availability of payment methods with an expression
    Given I proceed as the Admin
    And login as administrator
    When go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And I fill "Integration Form" with:
      | Type        | Check/Money Order |
      | Name        | Check/Money Order |
      | Label       | Check/Money Order |
      | Short Label | Check/Money Order |
      | Pay To      | Address           |
      | Send To     | Address           |
    And save and close form
    Then I should see "Integration saved" flash message

    When I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "Payment Rule Form" with:
      | Enable     | true               |
      | Name       | Check/Money Order  |
      | Sort Order | 1                  |
      | Currency   | $                  |
      | Expression | shippingAddress = null or shippingOrigin = null|
      | Method     | [Check/Money Order]|
    And save and close form
    Then should see "Payment rule has been saved" flash message

    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    Then I should not see "Check/Money Order"
