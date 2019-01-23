@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Payment Term in Customer grid view
  In order to be able to successfully save and use Customer Grid views
  As an administrator
  I want to be able to save Customers Grid view without Payment Term column

  Scenario: Grid view without Payment Term column is successfully saved and displayed
    And I login as administrator
    When I go to Customers/Customers
    Then I should see following grid:
      | Name      | Account   | Payment term |
      | Company A | Company A | net 10       |
    And I hide column Payment Term in grid
    When I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "without_payment_term" in "name"
    And I click "Save" in modal window
    Then I should see "View has been successfully created" flash message
    When I reload the page
    Then I should see following grid:
      | Name      | Account   |
      | Company A | Company A |
