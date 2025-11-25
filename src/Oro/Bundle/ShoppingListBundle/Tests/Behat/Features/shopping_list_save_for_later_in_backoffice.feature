@regression
@fixture-OroShoppingListBundle:ShoppingListWithSaveForLaterFixture.yml

Feature: Shopping list Save for later in Backoffice

  Scenario: Enable Save For Later Feature
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default | false |
      | Enable Save For Later             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check grid
    When I go to Sales/Shopping Lists
    And I click View Shopping List in grid
    Then I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product       | Quantity | Unit  | Notes                           |
      | product-kit-01    | Product Kit 1 | 2        | piece | Product Kit 1 Line Item 1 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece | Product Kit 1 Line Item 2 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece |                                 |
      | simple-product-02 | Product 2     | 1        | piece |                                 |
      | simple-product-04 | Product 4     | 1        | item  |                                 |
      | simple-product-05 | Product 5     | 2        | item  |                                 |
      | simple-product-06 | Product 6     | 3        | item  |                                 |

  Scenario: Check sorting in grid
    When sort "Shopping List Saved For Later Line Items Grid" by "Product"
    Then I should see that Product in 1 row is equal to "Product 2"
    When I sort "Shopping List Saved For Later Line Items Grid" by "Product" again
    Then I should see that Product in 1 row is equal to "Product Kit 1"

    When sort "Shopping List Saved For Later Line Items Grid" by "SKU"
    Then I should see that SKU in 1 row is equal to "product-kit-01"
    When I sort "Shopping List Saved For Later Line Items Grid" by "SKU" again
    Then I should see that SKU in 1 row is equal to "simple-product-06"

    When sort "Shopping List Saved For Later Line Items Grid" by "Quantity"
    Then I should see that Quantity in 1 row is equal to "1"
    When I sort "Shopping List Saved For Later Line Items Grid" by "Quantity" again
    Then I should see that Quantity in 1 row is equal to "3"

    When sort "Shopping List Saved For Later Line Items Grid" by "Unit"
    Then I should see that Unit in 1 row is equal to "item"
    When I sort "Shopping List Saved For Later Line Items Grid" by "Unit" again
    Then I should see that Unit in 1 row is equal to "piece"
    And sort "Shopping List Saved For Later Line Items Grid" by "SKU"

  Scenario: Check filters in grid
    When I filter SKU as contains "product-kit-01" in "Shopping List Saved For Later Line Items Grid"
    Then I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU            | Product       | Quantity | Unit  | Notes                           |
      | product-kit-01 | Product Kit 1 | 2        | piece | Product Kit 1 Line Item 1 Notes |
      | product-kit-01 | Product Kit 1 | 1        | piece | Product Kit 1 Line Item 2 Notes |
      | product-kit-01 | Product Kit 1 | 1        | piece |                                 |
    And I reset "SKU" filter in "Shopping List Saved For Later Line Items Grid"

    When I filter Product as contains "Product 2" in "Shopping List Saved For Later Line Items Grid"
    Then I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product   | Quantity | Unit  | Notes |
      | simple-product-02 | Product 2 | 1        | piece |       |
    And I reset "Product" filter in "Shopping List Saved For Later Line Items Grid"

    When I filter Quantity as equals "3" in "Shopping List Saved For Later Line Items Grid"
    Then I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product   | Quantity | Unit | Notes |
      | simple-product-06 | Product 6 | 3        | item |       |
    And I reset "Quantity" filter in "Shopping List Saved For Later Line Items Grid"

    When I check "item" in Unit filter in "Shopping List Saved For Later Line Items Grid"
    Then I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product   | Quantity | Unit | Notes |
      | simple-product-04 | Product 4 | 1        | item |       |
      | simple-product-05 | Product 5 | 2        | item |       |
      | simple-product-06 | Product 6 | 3        | item |       |
    And I reset "Unit" filter in "Shopping List Saved For Later Line Items Grid"

  Scenario: Check actions in grid
    Given I should see following actions for simple-product-06 in "Shopping List Saved For Later Line Items Grid":
      | View Product   |
      | Edit Line Item |
      | Delete         |

    When I click "Delete" on row "simple-product-06" in grid "Shopping List Saved For Later Line Items Grid"
    And I confirm deletion
    Then I should see "Shopping List Line Item deleted" flash message
    And I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product       | Quantity | Unit  | Notes                           |
      | product-kit-01    | Product Kit 1 | 2        | piece | Product Kit 1 Line Item 1 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece | Product Kit 1 Line Item 2 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece |                                 |
      | simple-product-02 | Product 2     | 1        | piece |                                 |
      | simple-product-04 | Product 4     | 1        | item  |                                 |
      | simple-product-05 | Product 5     | 2        | item  |                                 |

    When I click "Edit Line Item" on row "simple-product-05" in grid "Shopping List Saved For Later Line Items Grid"
    And I fill form with:
      | Quantity | 3               |
      | Notes    | Notes Product 5 |
    And click "Save"
    Then should see "Line item has been updated" flash message
    And I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product       | Quantity | Unit  | Notes                           |
      | product-kit-01    | Product Kit 1 | 2        | piece | Product Kit 1 Line Item 1 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece | Product Kit 1 Line Item 2 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece |                                 |
      | simple-product-02 | Product 2     | 1        | piece |                                 |
      | simple-product-04 | Product 4     | 1        | item  |                                 |
      | simple-product-05 | Product 5     | 3        | item  | Notes Product 5                 |

    When I click "View Product" on row "simple-product-02" in grid "Shopping List Saved For Later Line Items Grid"
    Then I should see that "Page Title" contains "simple-product-02 - Product 2"
    And the url should match "/admin/product/view"

  Scenario: Check Add Line Item
    When I go to Sales/Shopping Lists
    And I click View Shopping List in grid
    When I click "Add Line Item"
    And I fill form with:
      | Product  | simple-product-05             |
      | Quantity | 1                             |
      | Unit     | item                          |
      | Notes    | Add Line Item Product 5 Notes |
    And I click "Save" in modal window
    Then I should see "Line item has been added" flash message
    And I should see following "Shopping list Line items Grid" grid:
      | SKU               | Product   | Quantity | Unit | Notes                         |
      | simple-product-05 | Product 5 | 1        | item | Add Line Item Product 5 Notes |

  Scenario: Check Duplicate List
    When I click "Duplicate List"
    Then I should see "Shopping List (copied "
    And I should see following "Shopping list Line items Grid" grid:
      | SKU               | Product   | Quantity | Unit | Notes                         |
      | simple-product-05 | Product 5 | 1        | item | Add Line Item Product 5 Notes |
    And I should see following "Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product       | Quantity | Unit  | Notes                           |
      | product-kit-01    | Product Kit 1 | 2        | piece | Product Kit 1 Line Item 1 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece | Product Kit 1 Line Item 2 Notes |
      | product-kit-01    | Product Kit 1 | 1        | piece |                                 |
      | simple-product-02 | Product 2     | 1        | piece |                                 |
      | simple-product-04 | Product 4     | 1        | item  |                                 |
      | simple-product-05 | Product 5     | 3        | item  | Notes Product 5                 |

  Scenario: Check Create Order
    When I click "Create Order"
    Then I should see "simple-product-05 - Product 5"
    And I should not see "Product Kit 1 Line Item 1 Notes"
