@ticket-BB-14721
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:Products_quick_order_form_find_product.yml
@automatically-ticket-tagged

Feature: Quick order form autocomplete product suggestion
  In order to provide customers with ability to quickly start an order
  As buyer
  I need to be able to enter products sku, find needed one from autocomplete suggestion, select it and get price

  Scenario: Check prices when filling quick order form items with same SKU, and selecting different suggested products
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    And I type "P" in "Quick Order Form > SKU1"
    Then I should see following search suggestions:
      | PG-PA103 - PG-PA103 Product1 |
      | PA103-PG - Product3 PA103-PG |
      | P-A103 - Product2            |
    When I click on "Quick Order Form > SKU1 > AutoComplete Results > Item" with title "PG-PA103 - A103 Product1"
    And I wait for products to load
    And I type "P" in "Quick Order Form > SKU2"
    Then I should see following search suggestions:
      | PG-PA103 - PG-PA103 Product1 |
      | PA103-PG - Product3 PA103-PG |
      | P-A103 - Product2            |
    When I click on "Quick Order Form > SKU2 > AutoComplete Results > Item" with title "PA103-PG - Product3 PA103-PG"
    And I wait for products to load
    And I type "P" in "Quick Order Form > SKU3"
    Then I should see following search suggestions:
      | PG-PA103 - PG-PA103 Product1 |
      | PA103-PG - Product3 PA103-PG |
      | P-A103 - Product2            |
    When I click on "Quick Order Form > SKU3 > AutoComplete Results > Item" with title "P-A103 - Product2"
    And I wait for products to load
    Then "QuickAddForm" must contains values:
      | SKU1  | PG-PA103 - PG-PA103 Product1 |
      | QTY1  | 1                            |
      | UNIT1 | item                         |
      | SKU2  | PA103-PG - Product3 PA103-PG |
      | QTY2  | 1                            |
      | UNIT2 | item                         |
      | SKU3  | P-A103 - Product2            |
      | QTY3  | 1                            |
      | UNIT3 | item                         |
    Then "PG-PA103" product should has "$45.00" value in price field
    And "PA103-PG" product should has "$5.00" value in price field
    And "P-A103" product should has "$22.00" value in price field

  Scenario: Check prices when filling RFQ items with same SKU, and selecting different suggested products
    Given I am on homepage
    And I click "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I click "RFQ > SKU1 > DropDown"
    When I type "P" in "Search Suggestions Field"
    And I wait for products to load
    Then I should see following search suggestions:
      | PG-PA103 PG-PA103 Product1 |
      | PA103-PG Product3 PA103-PG |
      | P-A103 Product2            |
    When I click on "Search Suggestions Results" with title "PG-PA103 PG-PA103 Product1"
    And I click "RFQ > SKU1 > Target Price DropDown"
    And I click "$45.00"
    And click "Update Line Item"
    And I click "Add Another Product"
    And I click "RFQ > SKU2 > DropDown"
    When I type "P" in "Search Suggestions Field"
    And I wait for products to load
    Then I should see following search suggestions:
      | PG-PA103 PG-PA103 Product1 |
      | PA103-PG Product3 PA103-PG |
      | P-A103 Product2            |
    When I click on "Search Suggestions Results" with title "PA103-PG Product3 PA103-PG"
    And I click "RFQ > SKU2 > Target Price DropDown"
    And I click "$5.00"
    And click "Update Line Item"
    And I click "Add Another Product"
    And I click "RFQ > SKU3 > DropDown"
    When I type "P" in "Search Suggestions Field"
    And I wait for products to load
    Then I should see following search suggestions:
      | PG-PA103 PG-PA103 Product1 |
      | PA103-PG Product3 PA103-PG |
      | P-A103 Product2            |
    When I click on "Search Suggestions Results" with title "P-A103 Product2"
    And I click "RFQ > SKU3 > Target Price DropDown"
    And I click "$22.00"
    And click "Update Line Item"
    And click "Submit Request"
    Then should see "Request has been saved" flash message
