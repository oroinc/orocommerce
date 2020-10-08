@regression
@ticket-BB-19825
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroPaymentTermBundle:CustomerCustomerGroupFixture.yml
Feature: Hidden Payment Term in Customer grid view
  In order to be able to successfully save and use Customer Grid views
  As an administrator
  I want to be able to customize Customers Grid view, turn on and turn off Payment Term column

  Scenario: Customer grid view with hidden Payment Term column is successfully displayed
    And I login as administrator
    When I go to Customers/Customers
    Then I should see following grid with exact columns order:
      | Name      | Group         | Parent Customer | Internal Rating | Payment term | Tax Code | Account   |
      | Company A |               |                 |                 | net 10       |          | Company A |
      | Company B | All Customers |                 |                 | net 10       |          | Company B |
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Customer"
    And I click view "Customer" in grid
    And I click edit "Payment Term" in grid
    And I fill form with:
      | Add To Grid Settings | Yes and do not display |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message
    When I go to Customers/Customers
    Then I should see following grid with exact columns order:
      | Name      | Group         | Parent Customer | Internal Rating | Tax Code | Account   |
      | Company A |               |                 |                 |          | Company A |
      | Company B | All Customers |                 |                 |          | Company B |
    And I should not see "net 10"
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Customer"
    And I click view "Customer" in grid
    And I click edit "Payment Term" in grid
    And I fill form with:
      | Add To Grid Settings | No |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message
    When I go to Customers/Customers
    Then I should see following grid with exact columns order:
      | Name      | Group         | Parent Customer | Internal Rating | Payment term                        | Tax Code | Account   |
      | Company A |               |                 |                 |                                     |          | Company A |
      | Company B | All Customers |                 |                 | net 10 (Defined for Customer Group) |          | Company B |
