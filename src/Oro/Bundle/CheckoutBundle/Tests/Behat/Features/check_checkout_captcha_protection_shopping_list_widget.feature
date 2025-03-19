@regression
@behat-test-env
@ticket-BB-25175
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Check checkout captcha protection shopping list widget

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
    And I check "Start Checkout Form"

    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Enable guest shopping list setting
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Enable Guest Shopping List" checkbox should not be checked

    When uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"

    And uncheck "Use default" for "Shopping List Limit" field
    And fill in "Shopping List Limit" with "1"

    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Enable guest checkout setting
    When I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check CAPTCHA protection Shopping List
    Given I proceed as the Buyer
    And am on homepage
    When I type "SKU123" in "search"
    And click "Search Button"
    And click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it

    When I open shopping list widget
    And I click "Checkout"
    Then I should see "Additional Verification"
    And I should see "Please complete the CAPTCHA challenge to confirm that you are not a robot. This simple step helps ensure an enjoyable browsing experience for genuine users."

    When I click "Continue"
    Then I should see "Verification failed: Your submission did not pass the anti-bot check. Please try again or contact support if you continue to experience issues."

    When I fill in "Checkout Captcha Field" with "valid"
    And I click "Continue"
    Then Page title equals to "Enter Credentials - Checkout"
