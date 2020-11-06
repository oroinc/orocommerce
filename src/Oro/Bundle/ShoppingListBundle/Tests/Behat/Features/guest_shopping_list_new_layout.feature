@regression
@ticket-BB-19455
@fixture-OroShoppingListBundle:GuestShoppingListsFixture.yml
Feature: Guest Shopping List with new layout
  In order to manage shopping lists on front store
  As a Guest
  I need to be able to manage shopping list using actions on shopping list edit page

  Scenario: Feature Background
    Given I enable configuration options:
      | oro_shopping_list.availability_for_guests                |
      | oro_shopping_list.shopping_lists_page_enabled         |
      | oro_shopping_list.use_new_layout_for_view_and_edit_pages |
      | oro_checkout.guest_checkout                              |

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Create configurable attributes
    Given I proceed as the Admin
    When I login as administrator
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And save and close form
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    And I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Add new attributes to product family
    When I go to Products/ Product Families
    And I click Edit Default Family in grid
    And fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Prepare simple products
    When I go to Products/Products
    And I filter SKU as is equal to "<SKU>"
    And I click Edit <SKU> in grid
    And I fill in product attribute "Color" with "<Color>"
    And I fill in product attribute "Size" with "<Size>"
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | <Image>       |
      | Title | <SKU>_<Image> |
    And I click "Upload"
    And click on <SKU>_<Image> in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU   | Color | Size | Image    |
      | 1GB81 | Black | L    | cat1.jpg |
      | 1GB82 | White | M    | cat2.jpg |

  Scenario: Set configurable product variants
    When I go to Products/ Products
    And I click Edit "1GB83" in grid
    And I check "Color Product Attribute" element
    And I check "Size Product Attribute" element
    And I save form
    And I check 1GB81 and 1GB82 in grid
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create Shopping List as unauthorized user
    Given I proceed as the Guest
    When I am on homepage
    And I type "SKU003" in "search"
    And I click "Search Button"
    When I fill "ProductLineItemForm" with:
      | Quantity | 3 |
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    And I type "PSKU1" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    When I open shopping list widget
    And I click "View List"
    Then I should see following grid:
      | SKU    | Item     |          | QtyUpdate All | Price | Subtotal |
      | PSKU1  | Product1 | In Stock | 1 each        | $1.00 | $1.00    |
      | SKU003 | Product3 | In Stock | 3 each        | $3.00 | $9.00    |
    And I should not see a "Shopping List Actions" element

  Scenario: Add Shopping List notes and Line item notes
    When I click on "Add a note to entire Shopping List"
    And I type "My shopping list notes" in "Shopping List Notes Area"
    And I click "Apply"
    Then I should see "My shopping list notes" in the "Shopping List Notes" element
    When I click "Add Shopping List item Note"
    Then I should see "UiWindow" with elements:
      | Title        | Add note to "Product1" product |
      | okButton     | Add                            |
      | cancelButton | Cancel                         |
    When I type "Note for Product1" in "Line Item Notes Area"
    And click "Add" in modal window
    Then should see "Line item note has been successfully updated" flash message
    And I should see following grid:
      | SKU    | Item                       |          | QtyUpdate All | Price | Subtotal |
      | PSKU1  | Product1 Note for Product1 | In Stock | 1 each        | $1.00 | $1.00    |
      | SKU003 | Product3                   | In Stock | 3 each        | $3.00 | $9.00    |

  Scenario: Edit Shopping List notes and Line item notes
    When I click on "Edit Shopping List Notes"
    And I type "My shopping list updated notes" in "Shopping List Notes Area"
    And I click "Apply"
    Then I should see "My shopping list updated notes" in the "Shopping List Notes" element
    When I click "Edit Shopping List Line Item Note"
    Then I should see "UiWindow" with elements:
      | Title        | Edit note for "Product1" product |
      | okButton     | Save                             |
      | cancelButton | Cancel                           |
    When I type "Updated note for Product1" in "Line Item Notes Area"
    And click "Save" in modal window
    Then should see "Line item note has been successfully updated" flash message
    And I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price | Subtotal |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00 | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00 | $9.00    |

  Scenario: Add empty matrices to the shopping Shopping List
    When I type "1GB83" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List"
    Then should see 'Shopping list "Shopping List" was updated successfully' flash message
    When I open shopping list widget
    And I click "View List"
    Then I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All                   | Price | Subtotal |
      | 1GB83  | Slip-On Clog                       |          | Click "edit" to select variants |       | N/A      |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each                          | $1.00 | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each                          | $3.00 | $9.00    |
    And I should see following actions for 1GB83 in grid:
      | Edit   |
      | Delete |
    When I click Delete 1GB83 in grid
    Then I should see "Are you sure you want to delete this product?"
    When click "Cancel" in modal window
    And I click on "Create Order"
    Then I should see "UiWindow" with elements:
      | Content      | Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products? |
      | okButton     | Proceed                                                                                                                        |
      | cancelButton | Cancel                                                                                                                         |
    And click "Cancel" in modal window

  Scenario: Add configurable product to the Shopping List
    When I type "1GB83" in "search"
    And I click "Search Button"
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |       | L | M |
      | Black | 2 | - |
      | White | - | 3 |
    And I click "Update Shopping List"
    Then should see 'Shopping list "Shopping List" was updated successfully' flash message
    When I open shopping list widget
    And I click "View List"
    Then I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      | 1GB81  | Slip-On Clog Color: Black Size: L  | In Stock | 2 item        | $10.00 | $20.00   |
      | 1GB82  | Slip-On Clog Color: White Size: M  | In Stock | 3 item        | $7.00  | $21.00    |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00  | $9.00    |

  Scenario: Check Group similar
    When I click "Group similar"
    Then I should see "3 total records"
    And I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      |        | Slip-On Clog                       |          | 5 items       |        | $41.00   |
      | 1GB81  | Color: Black Size: L               | In Stock | 2 item        | $10.00 | $20.00   |
      | 1GB82  | Color: White Size: M               | In Stock | 3 item        | $7.00  | $21.00    |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00  | $9.00    |
    When I reload the page
    Then I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      |        | Slip-On Clog                       |          | 5 items       |        | $41.00   |
      | 1GB81  | Color: Black Size: L               | In Stock | 2 item        | $10.00 | $20.00   |
      | 1GB82  | Color: White Size: M               | In Stock | 3 item        | $7.00  | $21.00    |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00  | $9.00    |

  Scenario: Check filter by SKU
    When I filter SKU as contains "1GB81"
    Then I should see "1 total records"
    And I should see following grid:
      | SKU    | Item                            |          | QtyUpdate All | Price  | Subtotal |
      |        | Slip-On Clog                    |          | 5 items       |        | $41.00   |
      | 1GB81  | Color: Black Size: L And 1 more | In Stock | 2 item        | $10.00 | $20.00   |
    When click on "Add 1 More Variants"
    And I should see following grid:
      | SKU    | Item                 |          | QtyUpdate All | Price  | Subtotal |
      |        | Slip-On Clog         |          | 5 items       |        | $41.00   |
      | 1GB81  | Color: Black Size: L | In Stock | 2 item        | $10.00 | $20.00   |
      | 1GB82  | Color: White Size: M | In Stock | 3 item        | $7.00  | $21.00   |

  Scenario: Check Availability filter
    When I reset grid
    And I check "Out of Stock" in Availability filter
    Then there are no records in grid
    And I should see "No shopping list line items were found to match your search. Try modifying your search criteria"
    When I reset grid
    And I check "In Stock" in Availability filter
    Then I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      | 1GB81  | Slip-On Clog Color: Black Size: L  | In Stock | 2 item        | $10.00 | $20.00   |
      | 1GB82  | Slip-On Clog Color: White Size: M  | In Stock | 3 item        | $7.00  | $21.00   |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00  | $9.00    |

  Scenario: Check Quantity filter
    When I reset grid
    And I filter Quantity as greater than "4"
    Then there are no records in grid
    And I should see "No shopping list line items were found to match your search. Try modifying your search criteria"
    When I reset grid
    And I filter Quantity as less than "3"
   Then I should see following grid:
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      | 1GB81  | Slip-On Clog Color: Black Size: L  | In Stock | 2 item        | $10.00 | $20.00   |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |

  Scenario: Check Unit filter
    When I reset grid
    And I check "each" in Unit filter
    Then I should see "2 total records"
      | SKU    | Item                               |          | QtyUpdate All | Price  | Subtotal |
      | PSKU1  | Product1 Updated note for Product1 | In Stock | 1 each        | $1.00  | $1.00    |
      | SKU003 | Product3                           | In Stock | 3 each        | $3.00  | $9.00    |

  Scenario: Check Image preview
    When I reset grid
    And I filter SKU as is equal "1GB81"
    Then I should not see an "Popup Gallery Widget" element
    When I click "Product Item Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element
    And I should see gallery image with alt "Slip-On Clog"
    When I click "Popup Gallery Widget Close"
    Then I should not see an "Popup Gallery Widget" element

  Scenario: Check when no image
    When I reset grid
    And I filter SKU as is equal "PSKU1"
    Then I should not see an "Popup Gallery Widget" element
    And I should see an "Empty Line Item Product Image" element
    And I should not see an "Product Item Gallery Trigger" element
