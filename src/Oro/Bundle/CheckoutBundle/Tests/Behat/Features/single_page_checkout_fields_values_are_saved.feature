@ticket-BB-15018
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single Page Checkout Fields Values Are Saved
  In order to not enter checkout information more than once
  As a Customer User
  I want DNSLT, PO Number and Notes fields to show saved values after page reload

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I activate "Single Page Checkout" workflow

  Scenario: Create order from Shopping List 1 and verify quantity
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |

  Scenario: Check that "PO Number", "Notes" and "Do not ship later than" fields are not cleared after page reload
    Given I fill "Checkout Order Review Form" with:
      | Notes                  | Some Notes  |
      | Do not ship later than | 7/1/2018      |
    And I type "PO15" in "PO Number" from "Checkout Order Review Form"
    And I click on empty space
    # Wait till ajax query for state save transition will be triggered
    And I wait 1 second

    When I reload the page
    Then "Checkout Order Review Form" must contains values:
      | PO Number              | PO15        |
      | Notes                  | Some Notes  |
      | Do not ship later than | 7/1/2018      |

  Scenario: Check that "PO Number", "Notes" and "Do not ship later than" fields are not cleared after checkout is started from shopping list again
    Given I follow "Account"
    And I open page with shopping list List 1
    When I click "Create Order"
    Then "Checkout Order Review Form" must contains values:
      | PO Number              | PO15        |
      | Notes                  | Some Notes  |
      | Do not ship later than | 7/1/2018      |

  Scenario: Check that "PO Number", "Notes" and "Do not ship later than" fields are not cleared after user returns to the checkout from other page
    Given I follow "Account"
    And click "Orders"
    When click "Check Out" on row "List 1" in grid
    Then "Checkout Order Review Form" must contains values:
      | PO Number              | PO15        |
      | Notes                  | Some Notes  |
      | Do not ship later than | 7/1/2018      |

  Scenario: Complete checkout and verify fields values for Order from History
    Given I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should be on Order Frontend View page

    When I open Order History page on the store frontend
    Then there is no records in "OpenOrdersGrid"
    And I click "View" on row "1" in grid "PastOrdersGrid"
    And I should be on Order Frontend View page
    And I should see "PO Number PO15"
    And I should see "Do Not Ship Later Than 7/1/2018"
