@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@regression
@behat-test-env

Feature: Check generate PDF when Order is created

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Edit order confirmation template
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Emails/ Templates
    And I filter Template Name as Contains "order_confirmation_email"
    And I click edit "order_confirmation_email" in grid
    And I click "Add"
    And I fill "Email Template Form" with:
      | Attachments | Order Default PDF File |
    When I save form
    Then I should see "Template saved" flash message

  Scenario: Enabling the Generate PDF when Order is created option
    When go to System / Configuration
    And I follow "Commerce/Orders/Order Creation" on configuration sidebar
    And I fill "Configuration Order Creation Form" with:
      | Generate PDF When Order Is Created Use Default | false |
      | Generate PDF When Order Is Created             | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer downloads order PDF from storefront view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Checkout"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should contains the following:
      | From        | admin@example                            |
      | To          | AmandaRCole@example.org                  |
      | Subject     | Your Store Name order has been received. |
      | Attachments | order-1.pdf                              |

  Scenario: Disabling the Generate PDF when Order is created option
    Given I proceed as the Admin
    When go to System / Configuration
    And I follow "Commerce/Orders/Order Creation" on configuration sidebar
    And I fill "Configuration Order Creation Form" with:
      | Generate PDF When Order Is Created Use Default | false |
      | Generate PDF When Order Is Created             | false |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer not downloads order PDF from storefront view page
    Given I proceed as the Buyer
    When I open page with shopping list List 2
    And I click "Checkout"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should not contains the following:
      | Attachments | order-2.pdf |
