@regression
@ticket-BB-24400
@pricing-storage-flat

Feature: Displaying a customer page using a price list and flat storage
  Check if the customer page is displayed when using a default price list with a flat storage.

  Scenario: Create customer with price list using flat storage
    Given I login as administrator
    And go to Customers / Customers
    When I click "Create Customer"
    And fill "Customer Form" with:
      | Name       | Testing Customer   |
      | Price List | Default Price List |
    And save and close form
    Then I should see "Customer has been saved" flash message
    And should see Customer with:
      | Name | Testing Customer |
