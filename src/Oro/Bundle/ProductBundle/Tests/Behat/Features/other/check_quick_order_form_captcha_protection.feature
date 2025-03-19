@regression
@behat-test-env
@ticket-BB-25175
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Check Quick Order Form CAPTCHA protection

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable CAPTCHA protection
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/Integrations/CAPTCHA Settings" on configuration sidebar

    And uncheck "Use default" for "Enable CAPTCHA protection" field
    And I check "Enable CAPTCHA protection"

    And uncheck "Use default" for "CAPTCHA service" field
    And I fill in "CAPTCHA service" with "Dummy"

    And uncheck "Use default" for "Protect Forms" field
    And I check "Quick Order Form"

    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Login to storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Check CAPTCHA protection for Quick Order Form
    Given click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space

    When I fill in "Captcha" with "invalid"
    And I click "Get Quote"
    Then I should see "The form cannot be sent because you did not passed the anti-bot validation. If you are a human, please contact us." flash message
    And I close all flash messages

    When I fill in "Captcha" with "invalid"
    And I click "Create Order"
    Then I should see "The form cannot be sent because you did not passed the anti-bot validation. If you are a human, please contact us." flash message
    And I close all flash messages

    When I fill in "Captcha" with "invalid"
    And I click "Add to List 2"
    Then I should see "The form cannot be sent because you did not passed the anti-bot validation. If you are a human, please contact us." flash message
    And I close all flash messages

  Scenario: Check CAPTCHA protection pass for Quick Order Form - Get Quote
    When click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space

    When I fill in "Captcha" with "valid"
    And I click "Get Quote"
    Then I should see "Request a Quote"

  Scenario: Check CAPTCHA protection pass for Quick Order Form - Checkout
    When click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space

    When I fill in "Captcha" with "valid"
    And I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"

  Scenario: Check CAPTCHA protection pass for Quick Order Form - Checkout
    When click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space

    When I fill in "Captcha" with "valid"
    And I click "Add to List 2"
    Then I should see "1 product was added" flash message
