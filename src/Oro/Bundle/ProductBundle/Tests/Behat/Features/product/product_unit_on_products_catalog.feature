@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroProductBundle:ProductsWithUnits.yml
Feature: Product Unit On Products Catalog
  Check UI element of product unit on the store frontend

    Scenario: Create session
        Given I signed in as AmandaRCole@example.org on the store frontend
        And I click "NewCategory"
    
    Scenario: Unit label when product have only one unit
        Given I should see "SKU1"
        When I should see "ProductUnitLabel" for "SKU1" product
        Then I should not see "ProductUnitSelect" for "SKU1" product

    Scenario: Unit Select when product have few units
        Given I should see "SKU2"
        When I should not see "ProductUnitLabel" for "SKU2" product
        Then I should see "ProductUnitSelect" for "SKU2" product
