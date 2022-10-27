@regression
@ticket-BB-15779
@fixture-OroSaleBundle:QuoteCustomerUsers.yml
Feature: Check the customer user search field with name that is unique to all customer users but has a personal prefix
  In order to manage quotes
  As an Administrator
  I want to see a "quote" with field for customer users, which will have only one value that corresponds
  to the value of the customer field and will not see other customer users who are not bound to the selected customer

  Scenario: Create quote and check customer and customer user
    Given I login as administrator
    And go to Sales/Quotes
    When I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer                        | Acme_12 |
      | Customer User                   | Acme    |
      | LineItemProduct                 | SKU123  |
      | Overridden shipping cost amount | 10      |
    And I click "Save and Close"
    Then I should see text matching "Customer Acme_12"
    And I should see text matching "Customer User Acme_12"
