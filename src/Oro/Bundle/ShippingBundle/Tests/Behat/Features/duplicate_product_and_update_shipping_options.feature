@regression
@ticket-BB-14588
@fixture-OroShippingBundle:ProductDuplicateFixture.yml
Feature: Duplicate product and update shipping options
  In order to manage products
  As administrator
  I need to be able to save and duplicate product and all shipping options should be cloned
  Any changes of this copy should not affect the original product

  Scenario: Open original product and duplicate it
    Given I login as administrator
    And I go to Products/ Products
    And I click View Product1 in grid
    When I click "Duplicate"
    Then I should see product with:
      | SKU  | PSKU1-1  |
      | Name | Product1 |
    And I should see following product shipping options:
      | item | 1 kg | 1 x 1 x 1 cm | N/A |

  Scenario: Edit copied product
    Given I click "Edit"
    When fill "Product With Shipping Options Form" with:
      | SKU                                   | PSKU2    |
      | Name                                  | Product2 |
    And I click "Shipping Options"
    And fill "Product With Shipping Options Form" with:
      | Shipping Option Weight Value 1        | 10       |
      | Shipping Option Freight Class Value 1 | parcel   |
    And I save and close form
    Then I should see product with:
      | SKU  | PSKU2    |
      | Name | Product2 |
    And I should see following product shipping options:
      | item | 10 kg | 1 x 1 x 1 cm | parcel |

  Scenario: Verify that original product is not changed
    And I go to Products/ Products
    When I click View Product1 in grid
    And I should see product with:
      | SKU  | PSKU1    |
      | Name | Product1 |
    And I should see following product shipping options:
      | item | 1 kg | 1 x 1 x 1 cm | N/A |
