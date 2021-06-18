@fixture-OroProductBundle:product-quantity-negative.yml

Feature: Product quantity negative value and unit precision
  Input different values in quantity field
  Login as AmandaRCole@example.org on frontend commerce

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Check product quantity negative value
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When I type "-432423" in "ProductQuantityField"
    And I click on empty space
    Then I should see "ProductLineItemForm" validation errors:
      | Quantity | Quantity should be greater than 0 |
    When I type "43244." in "ProductQuantityField"
    Then ProductQuantityField field should has 43244.0000 value
    When I fill "ProductLineItemForm" with:
      | Unit | item |
    And I type "44." in "ProductQuantityField"
    Then ProductQuantityField field should has 44.000000 value
    When I type "123.456789" in "ProductQuantityField"
    Then ProductQuantityField field should has 123.456789 value
    When I type "1-+2+3.g4*567&f8999" in "ProductQuantityField"
    Then ProductQuantityField field should has 123.456789 value

  Scenario: Check precision field in product edit mode
    Given I proceed as the Admin
    And I login as administrator
    And I go to product with sku PSKU1 edit page
    When I type "34.423" in "ProductAdditionalPrecisionField"
    Then ProductAdditionalPrecisionField field should has 34423 value
    When I type "-432423" in "ProductAdditionalPrecisionField"
    And I click on empty space
    Then I should see "ProductForm" validation errors:
      | AdditionalPrecision | This value should be between 0 and 10. |
