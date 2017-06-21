@fixture-products.yml
@fixture-system_users.yml
@feature-BB-8377
Feature: Editing related products
  In order to propose my customer some other products
  As admin
  I need to be able to set related products to product

  Scenario: Check if datagrid of a product doesn't contain this product
    Given I login as administrator
    When go to Products/ Products
    And I click Edit "Product 1" in grid
    And I click "Select related products"
    And I filter SKU as contains "PSKU" in "SelectRelatedProductsGrid"
    Then I should see following "SelectRelatedProductsGrid" grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |
    And I click "Cancel"

  Scenario: Create relation
    Given go to Products/ Products
    And I click Edit "Product 1" in grid
    And I click "Select related products"
    And I should see following "SelectRelatedProductsGrid" grid:
      | Is Related  | SKU    | NAME      |
      | 0           | PSKU2  | Product 2 |
      | 0           | PSKU3  | Product 3 |
    When I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
    And I click "Select products"
    And I filter SKU as contains "PSKU" in "RelatedProductsEditGrid"
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
    And I click "Save and Close"
    Then I should see "Product has been saved" flash message
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |

  Scenario: Grid in popup should have related products checked
    Given go to Products/ Products
    And I click Edit "Product 1" in grid
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
    When I click "Select related products"
    Then I should see following "SelectRelatedProductsGrid" grid:
      | Is Related  | SKU    | NAME      |
      | 1           | PSKU2  | Product 2 |
      | 1           | PSKU3  | Product 3 |
      | 0           | PSKU4  | Product 4 |
    And I click "Cancel"

  Scenario: Change relation with quick edit
    Given go to Products/ Products
    And I click View Product 1 in grid
    And I click "Quick edit"
    And I click "Select related products"
    When I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU4 |
    And I click "Select products"
    And I select following records in grid:
      | PSKU2 |
    And I click "Delete" link from mass action dropdown
    And I click "Save and Close"
    Then I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |

  Scenario: Canceling edit will not affect related products
    Given go to Products/ Products
    And I click Edit Product 1 in grid
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |
    When I select following records in grid:
      | PSKU4 |
    And I click "Delete" link from mass action dropdown
    And I click "Cancel"
    And I click View Product 1 in grid
    Then I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |

  Scenario: Related products of inverse side should not be visible in admin panel
    Given go to Products/ Products
    And "Assign In Both Directions" option for related products is enabled
    When I click View PSKU3 in grid
    Then there is no records in "RelatedProductsViewGrid"

  Scenario: Related items should not be visible on view if user has no permission
    Given user has following permissions
      | Assign | Product               | None   |
      | Create | Product               | Global |
      | Delete | Product               | Global |
      | Edit   | Product               | Global |
    And I login as "CatalogManager1" user
    When I go to Products/ Products
      And I click View "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Related items should not be visible in product edition if user has no permission
    When I go to Products/ Products
      And I click Edit "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Disable related products functionality
    Given I login as administrator
    When go to System/ Configuration
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit Product 1 in grid
    And I should not see "RelatedProductsViewGrid" grid

  Scenario: Limit should be restricted
    Given go to System/ Configuration
      And I click "Related Items" on configuration sidebar
      And I fill "RelatedProductsConfig" with:
        | Enable Related Products                      | true  |
        | Maximum Number Of Assigned Items Use Default | false |
        | Maximum Number Of Assigned Items             | 2     |
      And I click "Save settings"
    When go to Products/ Products
      And I click Edit Product 1 in grid
      And I click "Select related products"
      And I select following records in "SelectRelatedProductsGrid" grid:
        | PSKU2 |
        | PSKU3 |
        | PSKU4 |
    Then "Select products" button is disabled
      And I should see "Limit of related products has been reached"
      And I click "Cancel"

  Scenario: Check related grid view after related product title has been updated
    Given go to System/ Configuration
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 25    |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit Product 1 in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
    And I click "Select products"
    And I click "Save and Close"
    And I should see "Product has been saved" flash message
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
    When go to Products/ Products
    And I click Edit PSKU2 in grid
    And I fill "ProductForm" with:
      | SKU   | PSKU22            |
      | Name  | Product 2 updated |
    And I click "Save and Close"
    Then go to Products/ Products
    And I click Edit "Product 1" in grid
    And I should see following grid:
      | SKU    | NAME              |
      | PSKU22 | Product 2 updated |

  Scenario: Check if relation is saved after disable/enable related items feature
    Given go to System/ Configuration
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit Product 1 in grid
    And I should not see "RelatedProductsViewGrid" grid
    When go to System/ Configuration
    And I click "Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | true  |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit Product 1 in grid
    And I should see following grid:
      | SKU    | NAME              |
      | PSKU22 | Product 2 updated |

  Scenario: Verify relation is removed in case when related product has been removed
    Given go to Products/ Products
    And I click Edit Product 1 in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU3 |
    And I click "Select products"
    And I click "Save and Close"
    And I should see "Product has been saved" flash message
    And I should see following grid:
      | SKU    | NAME              |
      | PSKU22 | Product 2 updated |
      | PSKU3  | Product 3         |
    And go to Products/ Products
    And I click Edit Product 2 updated in grid
    When I click "Delete"
    And I confirm deletion
    Then go to Products/ Products
    And I click Edit Product 1 in grid
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
