@regression
@ticket-BB-16482
@ticket-BB-18607
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroInventoryBundle:checkout.yml
Feature: Allowed inventory statuses configuration

  Scenario: Create window sessions
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Check product availability for the Order/Quote in the select dropdown and on hamburger grid
    Given I go to Sales/Orders
    When click "Create Order"
    And click "Add Product"
    Then I should see the following options for "Product" select in form "Order Form":
      | SKU1 - Product1 |
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    When I open select entity popup for field "Product" in form "Order Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
      | SKU1 | Product1 | In Stock         |
    And click on SKU2 in grid
    And click "Cancel"

    And go to Sales/Quotes
    When click "Create Quote"
    Then I should see the following options for "LineItemProduct" select in form "Quote Form":
      | SKU1 - Product1 |
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    When I open select entity popup for field "LineItemProduct" in form "Quote Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status | Category     |
      | SKU3 | Product3 | In Stock         | NewCategory3 |
      | SKU2 | Product2 | In Stock         | NewCategory2 |
      | SKU1 | Product1 | In Stock         | NewCategory  |
    When I filter "Category" as is equal to "NewCategory2"
    Then there is one record in grid
    And I should see following grid:
      | SKU  | Name     | Inventory Status | Category     |
      | SKU2 | Product2 | In Stock         | NewCategory2 |
    And click on SKU2 in grid
    And click "Cancel"

  Scenario: Check product availability for the Shopping/RFQ in the select dropdown and on hamburger grid
    Given go to Sales/Shopping Lists
    When click view "List 3" in grid
    And click "Add Line Item"
    Then I should see the following options for "Product" select:
      | SKU1 - Product1 |
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    When I open select entity popup for field "Product"
    Then I should see following "Add Products Popup" grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
      | SKU1 | Product1 | In Stock         |
    And click on SKU2 in grid
    And click "Cancel"

    And go to Sales/Requests For Quote
    When click edit "0111" in grid
    Then I should see the following options for "Line Item Product" select in form "Request Form":
      | SKU1 - Product1 |
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    When I open select entity popup for field "Line Item Product" in form "Request Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
      | SKU1 | Product1 | In Stock         |
    And click on SKU2 in grid
    And click "Cancel"

  Scenario: Check product availability for the Order/Quote in the select dropdown and on hamburger grid when product have discontinued status
    Given go to Products/Products
    And edit "SKU1" Inventory status as "Discontinued" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message
    And I go to Sales/Orders
    When click "Create Order"
    And click "Add Product"
    Then I should see the following options for "Product" select in form "Order Form":
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    And I should not see the following options for "Product" select in form "Order Form":
      | SKU1 - Product1 |
    When I open select entity popup for field "Product" in form "Order Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
    And there are 2 records in grid
    And click on SKU2 in grid
    And click "Cancel"

    And go to Sales/Quotes
    When click "Create Quote"
    Then I should see the following options for "LineItemProduct" select in form "Quote Form":
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    And I should not see the following options for "LineItemProduct" select in form "Quote Form":
      | SKU1 - Product1 |
    When I open select entity popup for field "LineItemProduct" in form "Quote Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
    And there are 2 records in grid
    And click on SKU2 in grid
    And click "Cancel"

  Scenario: Check product availability for the Shopping/RFQ in the select dropdown and on hamburger grid when product have discontinued status
    Given go to Sales/Shopping Lists
    When click view "List 3" in grid
    And click "Add Line Item"
    Then I should see the following options for "Product" select:
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    And I should not see the following options for "Product" select:
      | SKU1 - Product1 |
    When I open select entity popup for field "Product"
    Then I should see following "Add Products Popup" grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
    And number of records in "Add Products Popup" grid should be 2
    And click on SKU2 in grid
    And click "Cancel"

    And go to Sales/Requests For Quote
    When click edit "0111" in grid
    Then I should see the following options for "Line Item Product" select in form "Request Form":
      | SKU2 - Product2 |
      | SKU3 - Product3 |
    And I should not see the following options for "Line Item Product" select in form "Request Form":
      | SKU1 - Product1 |
    When I open select entity popup for field "Line Item Product" in form "Request Form"
    Then I should see following grid:
      | SKU  | Name     | Inventory Status |
      | SKU3 | Product3 | In Stock         |
      | SKU2 | Product2 | In Stock         |
    And there are 2 records in grid
    And click on SKU2 in grid
    And click "Cancel"

  Scenario: Allow discontinued for order, rfq, quote
    Given go to System / Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Visible Inventory Statuses" field
    And uncheck "Use default" for "Can Be Added to RFQs" field
    And uncheck "Use default" for "Can Be Added to Orders" field
    And fill form with:
      | Visible Inventory Statuses | [In Stock, Out of Stock, Discontinued] |
      | Can Be Added to RFQs       | [In Stock, Out of Stock, Discontinued] |
      | Can Be Added to Orders     | [In Stock, Out of Stock, Discontinued] |
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check that it is possible to create RFQ from the shopping list with Discontinued product
    Given I proceed as the Manager
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should see "Discontinued" for "SKU1" product on shopping list
    And I click "More Actions"
    And I click "Request Quote"
    And I fill form with:
      | PO Number | Test RFQ |
    And Request a Quote contains products
      | Product1 | 5 | item |
      | Product2 | 5 | item |
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And should see "REQUEST FOR QUOTE #2"
    And should see "Product2"

  Scenario: Check that it is possible to create Order from the shopping list with Discontinued product
    Given I open page with shopping list List 1
    And I click "Create Order"
    And I proceed as the Admin
    And go to System / Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And check "Use default" for "Visible Inventory Statuses" field
    And click "Save settings"
    And should see "Configuration saved" flash message
    When I proceed as the Manager
    And I go through the order completion, and should be on order view page
    Then I should see following "Order Line Items Grid" grid:
      | Product               |
      | Product1 Item #: SKU1 |
      | Product2 Item #: SKU2 |

  Scenario: Check that it is possible to ReOrder order with Discontinued product
    Given click "Re-Order"
    Then I go through the order completion, and should be on order view page
    And I should see following "Order Line Items Grid" grid:
      | Product               |
      | Product1 Item #: SKU1 |
      | Product2 Item #: SKU2 |

  Scenario: Check that discontinue product is not accessible through link
    Given go to "/product/view/1"
    Then I should see "404 Not Found"

  Scenario: Disallow discontinued for all front action
    Given I proceed as the Admin
    And uncheck "Use default" for "Visible Inventory Statuses" field
    And check "Use default" for "Can Be Added to RFQs" field
    And check "Use default" for "Can Be Added to Orders" field
    And fill form with:
      | Visible Inventory Statuses | [In Stock, Out of Stock, Discontinued] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that it is impossible to create Order from the shopping list with Discontinued product
    Given I proceed as the Manager
    When I open page with shopping list List 2
    And click "Create Order"
    Then I should see "Some products have not been added to this order. Please create an RFQ to request price."
    And should not see "Product1"

  Scenario: Check that it is impossible to create RFQ from the shopping list with Discontinued product
    Given I open page with shopping list List 2
    And click "More Actions"
    And click "Request Quote"
    Then I should see "Some products are not available and cannot be added to RFQ: Product1 (Item # SKU1)"

  Scenario: Check that it is impossible to create RFQ from the shopping list with Discontinued product
    Given I follow "Account"
    And click "Order History"
    When I click "Re-Order" on row "$23.00" in grid
    Then should see "Please note that the current order differs from the original one due to the absence or insufficient quantity in stock of the following products: SKU1."
