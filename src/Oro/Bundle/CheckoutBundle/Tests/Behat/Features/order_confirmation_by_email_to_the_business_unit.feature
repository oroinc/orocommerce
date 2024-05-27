@ticket-BAP-22562
@regression
@fixture-OroOrganizationBundle:BusinessUnit.yml
@fixture-OroLocaleBundle:LocalizationFixture.yml
@fixture-OroProductBundle:ProductUnitItemGermanTranslation.yml
@fixture-OroCheckoutBundle:CheckoutPaymentTerm.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:OrderConfirmationEmailLocalization.yml

Feature: Order confirmation by email to the business unit
  As an administrator, I want to be able to receive emails after creating an order to the email of the order
  holder's business unit.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Clone email template
    Given I proceed as the Admin
    And login as administrator
    And go to System / Emails / Templates
    And filter Template Name as is equal to "order_confirmation_email"
    When I click "Clone" on row "order_confirmation_email" in grid
    And fill form with:
      | Template Name | admin_order_confirmation_email  |
      | Subject       | Client order has been received. |
    And I save and close form
    Then I should see "Template saved" flash message

  Scenario: Create additional notification rule
    Given I go to System/ Emails/ Notification Rules
    When I click "Create Notification Rule"
    And fill form with:
      | Users                   | John Doe                       |
      | Entity Name             | Order                          |
      | Event Name              | Entity create                  |
      | Template                | admin_order_confirmation_email |
      | Additional Associations | Owner > Business Units         |
    And save and close form
    Then I should see "Notification Rule saved" flash message

  Scenario: Create order
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on the homepage
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And email with Subject "Your Store Name order has been received." containing the following was sent:
      | To | AmandaRCole@example.org |
    And email with Subject "Client order has been received." containing the following was sent:
      | To | admin@example.com |
