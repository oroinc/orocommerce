@regression
@ticket-BB-24249
@fixture-OroPricingBundle:ProductPricesWithMinimalOrderCount.yml

Feature: Product price with quantity less then minimal in price list
  In order to check that price calculation for products quantity less then minimal price list quantity
  As a Buyer
  I want to see correct price for product quantity less then minimum quantity in price list

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check quantity price with disable extra price calculation setting
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I open shopping list widget
    And I click "Shopping List 1" on shopping list widget
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "0.9" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price | Subtotal |
      | PSKU1                                                                   | 0.9 item       |       |          |
      | This item can't be added to checkout because the price is not available |                |       |          |
      | NEWPSKU2                                                                | 3 item         |       |          |
      | This item can't be added to checkout because the price is not available |                |       |          |
    And I go to the homepage

  Scenario: Enable fractional price calculation for quantity less then one
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then fill "PricingConfigurationForm" with:
      | Allow Fractional Quantity Price Calculation On Quantity Less Then One System | false |
      | Allow Fractional Quantity Price Calculation On Quantity Less Then One        | true  |
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check fractional quantity price for quantity less then one after enable setting
    Given I proceed as the Buyer
    When I go to the homepage
    And I open shopping list widget
    And I click "Shopping List 1" on shopping list widget
    And I click on "Shopping List Line Item 2 Quantity"
    And I type "1.5" in "Shopping List Line Item 2 Quantity Input"
    And I click on "Shopping List Line Item 2 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price | Subtotal |
      | PSKU1                                                                   | 1.5 item       |       |          |
      | This item can't be added to checkout because the price is not available |                |       |          |
      | NEWPSKU2                                                                | 3 item         |       |          |
      | This item can't be added to checkout because the price is not available |                |       |          |
    Then I click on "Shopping List Line Item 1 Quantity"
    And I type "0.5" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price   | Subtotal |
      | NEWPSKU2                                                                | 3 item         |         |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | PSKU1                                                                   | 0.5 item       | $100.00 | $50.00   |
    Then I click on "Shopping List Line Item 1 Quantity"
    And I type "8" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price   | Subtotal |
      | NEWPSKU2                                                                | 8 item         |         |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | PSKU1                                                                   | 0.5 item       | $100.00 | $50.00   |

  Scenario: Enable fractional price calculation for quantity less then minimum in price list
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then fill "PricingConfigurationForm" with:
      | Allow Fractional Quantity Price Calculation On Quantity Less Then One                   | false |
      | Allow Fractional Quantity Price Calculation On Quantity Less Then One System            | true  |
      | Allow Fractional Quantity Price Calculation On Quantity Less Then Minimum Priced System | false |
      | Allow Fractional Quantity Price Calculation On Quantity Less Then Minimum Priced        | true  |
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check fractional quantity price for quantity less then minimum in price list
    Given I proceed as the Buyer
    When I go to the homepage
    And I open shopping list widget
    And I click "Shopping List 1" on shopping list widget
    And I click on "Shopping List Line Item 2 Quantity"
    And I type "1.5" in "Shopping List Line Item 2 Quantity Input"
    And I click on "Shopping List Line Item 2 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price   | Subtotal |
      | NEWPSKU2                                                                | 8 item         |         |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | PSKU1                                                                   | 1.5 item       | $100.00 | $150.00  |
    Then I click on "Shopping List Line Item 2 Quantity"
    And I type "0.5" in "Shopping List Line Item 2 Quantity Input"
    And I click on "Shopping List Line Item 2 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price   | Subtotal |
      | NEWPSKU2                                                                | 8 item         |         |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | PSKU1                                                                   | 0.5 item       | $100.00 | $50.00   |
    Then I click on "Shopping List Line Item 1 Quantity"
    And I type "3" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price   | Subtotal |
      | NEWPSKU2                                                                | 3 item         |         |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | PSKU1                                                                   | 0.5 item       | $100.00 | $50.00   |

  Scenario: Enable price calculation for quantity less then minimum in price list
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then fill "PricingConfigurationForm" with:
      | Allow Fractional Quantity Price Calculation On Quantity Less Then Minimum Priced        | false |
      | Allow Fractional Quantity Price Calculation On Quantity Less Then Minimum Priced System | true  |
      | Allow Quantity Price Calculation On Quantity Less Then Minimum Priced System            | false |
      | Allow Quantity Price Calculation On Quantity Less Then Minimum Priced                   | true  |
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check fractional quantity price for quantity less then priced in price list
    Given I proceed as the Buyer
    When I go to the homepage
    And I open shopping list widget
    And I click "Shopping List 1" on shopping list widget
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "1.5" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price  | Subtotal |
      | PSKU1                                                                   | 1.5 item       |        |          |
      | This item can't be added to checkout because the price is not available |                |         |          |
      | NEWPSKU2                                                                | 3 item         | $40.00 | $120.00  |
    Then I click on "Shopping List Line Item 1 Quantity"
    And I type "0.5" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price  | Subtotal |
      | PSKU1                                                                   | 0.5 item       |        |          |
      | This item can't be added to checkout because the price is not available |                |        |          |
      | NEWPSKU2                                                                | 3 item         | $40.00 | $120.00  |
    Then I click on "Shopping List Line Item 2 Quantity"
    And I type "8" in "Shopping List Line Item 2 Quantity Input"
    And I click on "Shopping List Line Item 2 Save Changes Button"
    And I should see following grid:
      | SKU                                                                     | Qty Update All | Price  | Subtotal |
      | PSKU1                                                                   | 0.5 item       |        |          |
      | This item can't be added to checkout because the price is not available |                |        |          |
      | NEWPSKU2                                                                | 8 item         | $40.00 | $320.00  |
