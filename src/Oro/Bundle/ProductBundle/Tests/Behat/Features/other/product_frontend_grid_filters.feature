@regression
@ticket-BB-4867
@fixture-OroProductBundle:product_frontend.yml
Feature: Check filter values for product grid on front store
  In order to filter products on front store
  As a Buyer
  I should see value limitations for grid filters

  Scenario: Check filter max length limitation for sku
    Given I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    And filter SKU as is equal to "asdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasd"
    Then I should see "Please enter at most 255 characters" flash message

  Scenario: Check filter max length limitation for any text
    When I click "NewCategory"
    And filter Any Text as contains "asdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasd"
    Then I should see "Please enter at most 255 characters" flash message

  Scenario: Check filter max length limitation for name
    When I click "NewCategory"
    And filter Name as is equal to "asdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasd"
    Then I should see "Please enter at most 255 characters" flash message
