@fixture-products.yml
@feature-BB-8377
Feature: Configuring related products
  In order to properly configure
  As admin
  I need to be able to change of related products configuration

  Scenario: Disable related products functionality
    Given I login as administrator
    When go to System/ Configuration
    And I click "Commerce"
    And I click "Catalog"
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit Product 1 in grid
    And I should not see "Grid"

  Scenario: Limit should be restricted
    Given go to System/ Configuration
    And I click "Commerce"
    And I click "Catalog"
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 2     |
    And I click "Save settings"
    When go to Products/ Products
    And I click Edit Product 1 in grid
    And I click "Select related products"
    And I select following records in SelectRelatedProductsGrid:
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |
    Then "Select products" button is disabled
