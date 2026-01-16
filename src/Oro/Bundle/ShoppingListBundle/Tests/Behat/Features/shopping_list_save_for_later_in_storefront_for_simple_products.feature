@regression
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@skip
# Will be unskip after BB-26772 resolved

Feature: Shopping list Save for later in Storefront for simple products

  Scenario: Check Shopping List Saved For Later in the Storefront edit page
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list Shopping List 1
    Then I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table
    And I should see "Summary 3 Items"
    And I should see "Subtotal $1,581.00"
    And I should see "Total: $1,581.00"

# Cancel confirmation should not modify shopping list state
  Scenario: Cancel saving product for later keeps it in the Shopping List
    When I click "Save For Later" on row "CC37" in grid "Frontend Shopping List Edit Grid"
    Then I should see "Are you sure you want to save this product for later?"
    When I click "Cancel" in confirmation dialogue
    Then I should see following "Frontend Shopping List Edit Grid" grid containing rows:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
    And I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table

# Move products from main list to "Saved for Later"
  Scenario: Save products for later moves them from list to Saved For Later section
    When I check all visible on page in "Frontend Shopping List Edit Grid"
    And I click "Save For Later" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    Then should see "Save Products For Later Selected products will be saved for later." in confirmation dialogue
    When I click "Yes, Save"
    Then I should see "3 product(s) have been saved for later successfully." flash message
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I shouldn't see Save For Later action in "Frontend Shopping List Edit Grid"
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36 Note 36 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock     | 17 piece       | $31.00 | $527.00  |

  Scenario: Edit and clear product notes in Saved For Later section
    Given sort "Frontend Shopping List Saved For Later Line Items Grid" by "SKU"
    When I click on the first "Frontend Shopping List Saved For Later Line Items Edit Note"
    And I fill in "Shopping List Notes in Popover" with "Update Note 36 text"
    And I focus on "UiPopover Submit Button"
    And I press "Space" key on "UiPopover Submit Button" element
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                        | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36 Update Note 36 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text        | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text        | In Stock     | 17 piece       | $31.00 | $527.00  |

    When I click on the first "Frontend Shopping List Saved For Later Line Items Edit Note"
    And I fill in "Shopping List Notes in Popover" with ""
    And I focus on "UiPopover Submit Button"
    And I press "Space" key on "UiPopover Submit Button" element
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock     | 17 piece       | $31.00 | $527.00  |
    And I should see following actions for CC36 in "Frontend Shopping List Saved For Later Line Items Grid":
      | Remove From "Saved For Later" |
      | Add a note                    |
      | Delete                        |

  Scenario: Edit quantity of a Saved For Later product
    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity Increment"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 18 piece       | $31.00 | $558.00  |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock     | 17 piece       | $31.00 | $527.00  |

    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity"
    And I type "1" in "Frontend Shopping List Saved For Later Line Item 1 Product Quantity Input"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 1 piece        | $31.00 | $31.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock     | 17 piece       | $31.00 | $527.00  |

  Scenario: Delete product from Saved For Later
    When I click "Delete" on row "CC38" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I confirm deletion
    Then I should see 'The "Product 38" product was successfully deleted' flash message
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 1 piece        | $31.00 | $31.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |

  Scenario: Move item from Saved For Later back to Shopping List
    When I click 'Remove From "Saved For Later"' on row "CC36" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see "Remove \"Product 36\" From \"Saved For Later\""
    And I should see "Are you sure you want to remove this product from \"Saved For Later\"?"
    When I click "Yes, Remove" in confirmation dialogue
    Then I should see "The \"Product 36\" product was removed from \"Saved For Later\"."
    And I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU  | Product    | Availability | Qty     | Price  | Subtotal |
      | CC36 | Product 36 | In Stock     | 1 piece | $31.00 | $31.00   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece       | $31.00 | $527.00  |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $31.00"
    And I should see "Total: $31.00"

# Duplicate list and ensure Saved For Later are also duplicated correctly
  Scenario: Duplicate Shopping List with Saved For Later items
    When I scroll to top
    And I click "Shopping List Actions"
    And I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And I should see "Shopping List 1 (copied "
    And I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU  | Product    | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36 | In Stock     | 1 piece        | $31.00 | $31.00   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty      | Price  | Subtotal |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece | $31.00 | $527.00  |

  Scenario: Add product back to Shopping List and save for later again
    When type "CC37" in "search"
    And I click "Search Button"
    And I click "Shopping List Dropdown"
    And I click "Add to Shopping List 1" in "Shopping List Button Group Menu" element
    Then I should see 'Product has been added to "Shopping List 1"' flash message

    When I open page with shopping list Shopping List 1
    And I click "Save For Later" on row "CC37" in grid "Frontend Shopping List Edit Grid"
    Then I should see "Are you sure you want to save this product for later?"
    And I click "Yes, Save" in confirmation dialogue

    When I click "Save For Later" on row "CC36" in grid "Frontend Shopping List Edit Grid"
    Then I should see "Are you sure you want to save this product for later?"
    And I click "Yes, Save" in confirmation dialogue

    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 1 piece        | $31.00 | $31.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 18 piece       | $31.00 | $558.00  |

  Scenario: Check "Move to" for Saved For Later
#  Open Move dialog and cancel — grid should remain unchanged
    When I check CC36 record in "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I check CC37 record in "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I click "Move to" link from mass action dropdown in "Frontend Shopping List Saved For Later Line Items Grid"
    And I click on "UiDialog cancelButton"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 1 piece        | $31.00 | $31.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 18 piece       | $31.00 | $558.00  |

#  Try to move items to the same list — no items should move
    When I click "Move to" link from mass action dropdown in "Frontend Shopping List Saved For Later Line Items Grid"
    And I click "Filter Toggle" in "UiDialog" element
    And I filter Name as is equal to "Shopping List 1" in "Shopping List Action Move Grid"
    And I click "Show (1)"
    And I click "Shopping List Action Move Radio"
    And I click "Shopping List Action Submit"
    Then I should see "No items were moved." flash message and I close it
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 1 piece        | $31.00 | $31.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 18 piece       | $31.00 | $558.00  |

#  Move items to another list — both should move successfully
    When I check CC36 record in "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I check CC37 record in "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I click "Move to" link from mass action dropdown in "Frontend Shopping List Saved For Later Line Items Grid"
    And I click "Filter Toggle" in "UiDialog" element
    And I filter Name as contains "Shopping List 1 (copied" in "Shopping List Action Move Grid"
    And I click "Show (1)"
    And I click "Shopping List Action Move Radio"
    And I click "Shopping List Action Submit"
    Then I should see "2 items have been moved successfully." flash message and I close it
    And I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table

# Verify destination list now contains moved items
    When I open page with shopping list Shopping List 1 (copied
    Then I should see following "Frontend Shopping List Edit Grid" grid containing rows:
      | SKU  | Product                 | Availability | Qty Update All | Price  | Subtotal |
      | CC36 | Product 36              | In Stock     | 2 piece        | $31.00 | $62.00   |
      | CC37 | Product 37 Note 37 text | In Stock     | 18 piece       | $31.00 | $558.00  |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product                 | Availability | Qty      | Price  | Subtotal |
      | CC37 | Product 37 Note 37 text | In Stock     | 17 piece | $31.00 | $527.00  |

  Scenario: Reassign Shopping List to another user
    Given I should see "Assigned To: Amanda Cole"
    When I click "Shopping List Actions"
    And I click "Reassign"
    And I filter First Name as is equal to "Nancy" in "Shopping List Action Reassign Grid"
    And I click "Show (1)"
    And I click "Shopping List Action Reassign Radio"
    And I click "Shopping List Action Submit"
    Then I should see "Assigned To: Nancy Sallee"
