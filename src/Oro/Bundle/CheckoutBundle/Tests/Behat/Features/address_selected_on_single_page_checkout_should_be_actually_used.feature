@ticket-BB-18401
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:digital_products_taxes.yml

Feature: Address selected on single page checkout should be actually used
  In order for single page checkout to function properly
  As front store user
  First address in the select box should be used even if it's not set as default

  Scenario: Feature background
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to Customers / Customer User Roles
    And I click edit "Buyer" in grid
    And I uncheck "Enter the billing address manually"
    And I uncheck "Enter The Shipping Address Manually For Checkout" element
    When I save and close form
    Then I should see "Customer User Role has been saved" flash message
    Given I go to Customers / Customer Users
    And I click edit "AmandaRCole@example.org" in grid
    # Delete two of the three addresses
    And I click "Delete Address Button Edit Page"
    And I click "Delete Address Button Edit Page"
    And I uncheck "Default Billing"
    And I uncheck "Default Shipping"
    When I save and close form
    Then I should see "Customer User has been saved" flash message
    And I should see "ORO Fifth avenue 10115 Berlin Germany"
    And I activate "Single Page Checkout" workflow
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Destination |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Assert the tax is applied according to the only shipping address on the checkout page
    Given I proceed as the Manager
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Shopping List Widget"
    And I click "List 2"
    When I click "Create Order"
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Billing Address" select
    And I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Shipping Address" select
    And I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $2.00"
    And I should see "Total $25.00"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    Given I proceed as the Admin
    And I go to Sales / Orders
    When I click view "$25.00" in grid
    Then I should see "Billing Address Primary address ORO Fifth avenue 10115 Berlin Germany"
    And I should see "Shipping Address Primary address ORO Fifth avenue 10115 Berlin Germany"
