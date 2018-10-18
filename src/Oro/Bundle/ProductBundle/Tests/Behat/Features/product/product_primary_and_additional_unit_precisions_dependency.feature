@ticket-BB-7258
@automatically-ticket-tagged
Feature: Product primary and additional unit precisions dependency
  In order to prevent possibility to select same unit for primary and additional unit precisions
  As administrator
  I need not to be able to select same unit for primary and additional unit precisions

  Scenario: "Product Unit Precisions 1A" > CHECK IF NOT POSSIBLE TO SELECT SAME UNIT FOR PRIMARY AND ADDITIONAL UNIT PRECISIONS FOR NEW PRODUCT. PRIORITY - MAJOR
    Given I login as administrator
    And go to Products/ Products
    And click "Create Product"
    When I click "Continue"
    And I fill product fields with next data:
      | PrimaryUnit         | item      |
      | PrimaryPrecision    | 1         |
# TODO: After BB-13717 is fixed, return precision 0 here
#      | PrimaryPrecision    | 0         |
    Then I should see value "set" in "ProductPrimaryUnitField" options
    When I fill product fields with next data:
      | AdditionalUnit      | set       |
      | AdditionalPrecision | 1         |
# TODO: BB-13717, and also here
#      | AdditionalPrecision | 0         |
    Then I should not see value "set" in "ProductPrimaryUnitField" options
    And I should not see value "item" in "ProductAdditionalUnitField" options
    Then I save product with next data:
      | Name                | Product 1 |
      | SKU                 | SKU001    |
      | Status              | enabled   |
    And I should see "Product has been saved" flash message

  Scenario: "Product Unit Precisions 1B" > CHECK IF NOT POSSIBLE TO SELECT SAME UNIT FOR PRIMARY AND ADDITIONAL UNIT PRECISIONS FOR EXISTING PRODUCT. PRIORITY - MAJOR
    Given I go to product with sku SKU001 edit page
    And I should not see value "set" in "ProductPrimaryUnitField" options
    And I should not see value "item" in "ProductAdditionalUnitField" options
