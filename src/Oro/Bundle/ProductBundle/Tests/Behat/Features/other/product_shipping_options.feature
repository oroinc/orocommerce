@regression
@ticket-BB-19728
@fixture-OroProductBundle:ProductShippingOptions.yml

Feature: Product shipping options
  In order to manage products shipping options
  As administrator
  I change product shipping options and see that options are saved correctly and rendered correctly

  Scenario: Check product view with empty shipping options
    Given I login as administrator
    And go to Products/ Products
    And number of records should be 1
    When I click View Product in grid
    Then I should see following product shipping options:
      | item | N/A | N/A | N/A |

  Scenario: Check product shipping options validation
    Given I click "Edit"
    When I fill "ProductForm" with:
      | Shipping Option Weight Value 1            | 0.1234567890123 |
      | Shipping Option Dimensions Length Value 1 | 0.1234567890123 |
      | Shipping Option Dimensions Width Value 1  | 123456789012345 |
      | Shipping Option Dimensions Height Value 1 | 123456789012345 |
    Then I should see validation errors:
      | Shipping Option Weight Value 1            | This value is too long. It should have 14 characters or less. |
      | Shipping Option Dimensions Length Value 1 | This value is too long. It should have 14 characters or less. |
      | Shipping Option Dimensions Width Value 1  | This value is too long. It should have 14 characters or less. |
      | Shipping Option Dimensions Height Value 1 | This value is too long. It should have 14 characters or less. |

  Scenario: Check shipping options with valid value
    Given I fill "ProductForm" with:
      | Shipping Option Weight Value 1            | 0.123456789012 |
      | Shipping Option Dimensions Length Value 1 | 0.123456789012 |
      | Shipping Option Dimensions Width Value 1  | 12345678901234 |
      | Shipping Option Dimensions Height Value 1 | 123            |
    When I save and close form
    Then I should see "Product has been saved" flash message
    And should see following product shipping options:
      | item | 0.123456789012 kg | 0.123456789012 x 12345678901234 x 123 cm | parcel |
