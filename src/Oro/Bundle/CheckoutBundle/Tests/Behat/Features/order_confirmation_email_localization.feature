@ticket-BB-20953
@fixture-OroOrganizationBundle:BusinessUnit.yml
@fixture-OroLocaleBundle:LocalizationFixture.yml
@fixture-OroProductBundle:ProductUnitItemGermanTranslation.yml
@fixture-OroCheckoutBundle:CheckoutPaymentTerm.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:OrderConfirmationEmailLocalization.yml

Feature: Order confirmation email localization
  In order to create order from Shopping List on front store
  As a Buyer
  I want to start and complete checkout from shopping list on German localization
  email will sent with correctly translated labels

  Scenario: Feature Background
    And I enable the existing localizations

  Scenario: Switch to German localization
    Given I login as AmandaRCole@example.org buyer
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "German Localization" localization

  Scenario: Create order from Shopping List 1 with 5 line items
    Given I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And email with Subject "Your Store Name order has been received." containing the following was sent:
       | Body | 1 Element |

  Scenario: Create order from Shopping List 2 with 15 line items
    Given I open page with shopping list List 2
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And email with Subject "Your Store Name order has been received." containing the following was sent:
      | Body | 1 Element |
