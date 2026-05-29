@regression
@ticket-BB-26515
@fixture-OroShoppingListBundle:InvalidItemsDialogAfterRemoveFromSavedForLaterFixture.yml

Feature: Invalid items dialog after Remove From "Saved For Later"

  Scenario: Create sessions
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Enable Save for later
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default                                                  | false |
      | Enable Save For Later                                                              | true  |
      | Enable Enforce separate shopping list validations for checkout and RFQ Use default | false |
      | Enable Enforce separate shopping list validations for checkout and RFQ             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Change SKU1 inventory status to Discontinued
    Given go to Products/Products
    And edit "SKU1" Inventory status as "Discontinued" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Show Discontinued inventory status on the storefront
    Given go to System / Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Visible Inventory Statuses" field
    And fill form with:
      | Visible Inventory Statuses | [In Stock, Out of Stock, Discontinued] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer moves Discontinued item to Saved For Later
    Given I proceed as the Manager
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list Shopping List
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU                                                                                                                                                                | Availability | Price | Subtotal |
      | SKU1                                                                                                                                                               | Discontinued | $2.00 | $10.00   |
      | This item can't be added to checkout because the inventory status is not supported. This item can't be added to RFQ because the inventory status is not supported. |              |       |          |
      | SKU3                                                                                                                                                               | In Stock     | $2.00 | $10.00   |

    When I click "Save For Later" on row "SKU1" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save"
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU  | Availability | Price | Subtotal |
      | SKU3 | In Stock     | $2.00 | $10.00   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Availability | Price | Subtotal |
      | SKU1 | Discontinued | $2.00 | $10.00   |

  Scenario: Dialog opens after single Remove From "Saved For Later"
    When I click "Remove From "Saved For Later"" on row "SKU1" in grid
    And I click "Yes, Remove"
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU                                                                                                                                                                | Availability | Price | Subtotal |
      | SKU1                                                                                                                                                               | Discontinued | $2.00 | $10.00   |
      | This item can't be added to checkout because the inventory status is not supported. This item can't be added to RFQ because the inventory status is not supported. |              |       |          |
      | SKU3                                                                                                                                                               | In Stock     | $2.00 | $10.00   |

    When I click "Create Order"
    Then I should see "Invalid Items In The Order" in the "UiDialog Title" element
    And I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | Product1 SKU1 5 items $2.00 $10.00                                                  |
      | This item can't be added to checkout because the inventory status is not supported. |

    When I click on "DialogClose"
    And I click "Save For Later" on row "SKU1" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU  | Product  | Availability | Qty Update All | Price | Subtotal |
      | SKU1 | Product1 | Discontinued | 5 item         | $2.00 | $10.00   |

  Scenario: Dialog opens after Remove From "Saved For Later" mass action
    When I check SKU1 record in "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I click "Remove From \"Saved For Later\""
    And I click "Yes, Remove"
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU                                                                                                                                                                | Availability | Price | Subtotal |
      | SKU1                                                                                                                                                               | Discontinued | $2.00 | $10.00   |
      | This item can't be added to checkout because the inventory status is not supported. This item can't be added to RFQ because the inventory status is not supported. |              |       |          |
      | SKU3                                                                                                                                                               | In Stock     | $2.00 | $10.00   |

    When I click "Create Order"
    Then I should see "Invalid Items In The Order" in the "UiDialog Title" element
    And I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | Product1 SKU1 5 items $2.00 $10.00                                                  |
      | This item can't be added to checkout because the inventory status is not supported. |
