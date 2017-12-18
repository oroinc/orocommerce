@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroProductBundle:Products_quick_order_form.yml
Feature: Quote Backoffice Product Free-form Workflow
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Create quote with free-form product
    Given I login as administrator
    When go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Wholesaler B    |
      | Customer User    | Marlene Bradley |
      | LineItemProduct  | PSKU1           |
    Then LineItemPrice field should has 45 value
    When I type "10" in "LineItemPrice"
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    When I click "Price overridden button"
    And I click "Reset price"
    Then LineItemPrice field should has 45 value
    When I click "Free-form"
    Then LineItemPrice field is empty
    And I should see a "Disabled price overridden button" element
    When I fill "Quote Form" with:
      | LineItemPrice            | 10    |
      | LineItemFreeFormSku      | PSKU1 |
      | LineItemFreeFormProduct  | PSKU1 |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
