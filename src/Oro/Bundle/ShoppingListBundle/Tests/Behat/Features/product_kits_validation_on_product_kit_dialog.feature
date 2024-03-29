@feature-BB-21126
@ticket-BB-22446
@ticket-BB-22527
@ticket-BB-22557
@ticket-BB-22545
@fixture-OroShoppingListBundle:ProductKitInShoppingListFixture.yml

Feature: Product kits validation on product kit dialog

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And sessions active:
      | Buyer | second_session |

  Scenario: Open Configure and Add to Shopping List dialog
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "product-kit-1" in "search"
    And I click "Search Button"
    And I click "Product Kit 1"
    Then I should see an "Configure and Add to Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 1               |
      | Kit Item 1 Name      | Barcode Scanner             |
      | Kit Item 2 Name      | Base Unit                   |
      | Price                | Total: $41.00 |
      | Kit Item 1 Product 1 | CC23 Product 23 $31.00      |
      | Kit Item 1 Product 2 | None                        |
      | Kit Item 2 Product 1 | CC21 Product 21 $31.00      |
      | Kit Item 2 Product 2 | CC22 Product 22 $31.00      |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1 |

  Scenario Outline: Check quantity validation messages
    # Set correct value in order to see error messages one by one
    When I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 2 Quantity | 1 |
    And I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 1 |

    When I fill "<Form Name>" with:
      | <Field Name> | <Field Value> |
    And I click "Product Kit Dialog Shopping List Dropdown"
    When I click "Add to Shopping List 1" in "Shopping List Button Group Menu" element
    Then I should see "<Form Name>" validation errors:
      | <Field Name> | <Expected Error Message> |
    And I should see 1 element "Floating Error Message"

    Examples:
      | Form Name                         | Field Name                    | Field Value | Expected Error Message                          |
      | Product Kit Line Item Form        | Kit Item Line Item 2 Quantity |             | The quantity cannot be empty                    |
      | Product Kit Line Item Form        | Kit Item Line Item 2 Quantity | -1          | The quantity should be greater than 0.          |
      | Product Kit Line Item Form        | Kit Item Line Item 2 Quantity | 11          | The quantity should be between 1 and 10.        |
      | Product Kit Line Item Totals Form | Quantity                      |             | This value should not be blank.                 |
      | Product Kit Line Item Totals Form | Quantity                      | -1          | This value should be between 0 and 100,000,000. |
      | Product Kit Line Item Totals Form | Quantity                      | 100000001   | This value should be between 0 and 100,000,000. |

  Scenario: Check quantity as non-integer
    When I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 1 |
    And I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 2 Quantity | e1 |
    Then "Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 2 Quantity | 1 |
    And I should see 0 element "Floating Error Message"

    When I fill "Product Kit Line Item Totals Form" with:
      | Quantity | e1 |
    Then "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1 |
    And I should see 0 element "Floating Error Message"

  Scenario: Check Notes validation
    When I fill "Product Kit Line Item Form" with:
      | Notes | Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus.Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus. |
    Then I should see "Product Kit Line Item Form" validation errors:
      | Notes | This value is too long. It should have 2048 characters or less. |
    And I should see 0 elements "Floating Error Message"
