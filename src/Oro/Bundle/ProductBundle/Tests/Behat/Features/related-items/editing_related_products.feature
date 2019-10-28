@regression
@fixture-OroProductBundle:related_items_products.yml
@fixture-OroProductBundle:related_items_system_users.yml
@feature-BB-8377

Feature: Editing related products
  In order to propose my customer some other products
  As admin
  I need to be able to set related products to product

  Scenario: Check if datagrid of a product doesn't contain this product
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator
    When go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I filter SKU as contains "PSKU" in "SelectRelatedProductsGrid"
    Then I should see following "SelectRelatedProductsGrid" grid:
      | SKU   | NAME               |
      | PSKU5 | Product5(disabled) |
      | PSKU4 | Product 4          |
      | PSKU3 | Product 3          |
      | PSKU2 | Product 2          |
    And I click "Cancel" in modal window

  Scenario: Create relation
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I should see following "SelectRelatedProductsGrid" grid:
      | Is Related | SKU   | NAME               |
      | 0          | PSKU5 | Product5(disabled) |
      | 0          | PSKU4 | Product 4          |
      | 0          | PSKU3 | Product 3          |
      | 0          | PSKU2 | Product 2          |
    When I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
    And I click "Select products"
    And I filter SKU as contains "PSKU" in "RelatedProductsEditGrid"
    And I should see following "RelatedProductsEditGrid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |

  Scenario: Grid in popup should have related products checked
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    When I click "Select related products"
    Then I should see following "SelectRelatedProductsGrid" grid:
      | Is Related | SKU   | NAME               |
      | 1          | PSKU3 | Product 3          |
      | 1          | PSKU2 | Product 2          |
      | 0          | PSKU5 | Product5(disabled) |
      | 0          | PSKU4 | Product 4          |
    And I click "Cancel" in modal window

  Scenario: Change relation with quick edit
    Given go to Products/ Products
    And I click View PSKU1 in grid
    And I click "Quick edit"
    And I click "Select related products"
    When I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU4 |
    And I click "Select products"
    And I click "Delete" on row "PSKU2" in grid
    And I save and close form
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |

  Scenario: Canceling edit will not affect related products
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should see following "RelatedProductsEditGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
    And I click "Delete" on row "PSKU4" in grid
    And I click "Cancel"
    And I click View PSKU1 in grid
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |

  Scenario: Related products of inverse side should not be visible in admin panel
    Given go to Products/ Products
    And "Assign In Both Directions" option for related products is enabled
    When I click View PSKU3 in grid
    Then I should see "No records found"

  Scenario: Related items should not be visible on view if user has no permission
    Given user has following permissions
      | Assign | Product | None   |
      | Create | Product | Global |
      | Delete | Product | Global |
      | Edit   | Product | Global |
    Given I proceed as the Manager
    And I login as "CatalogManager1" user
    When I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Verify edit related products permission will not affect product creation
    When I go to Products/ Products
    And I click "Create Product"
    And I click "Continue"
    And fill "ProductForm" with:
      | SKU    | product12 |
      | Name   | product12 |
      | Status | Enabled   |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Related items should not be visible in product edition if user has no permission
    When I go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Related items should be visible on view if user has at least one permission to any of Related Items
    Given user has following entity permissions enabled
      | Edit Related Products |
    When I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should see "Related Items"
    And I should see "RelatedProductsViewGrid" grid

  Scenario: Related items should be visible on edit if user has at least one permission to any of Related Items
    Given I go to Products/ Products
    When I click Edit "PSKU1" in grid
    Then I should see "Related Items"
    And I should see "RelatedProductsEditGrid" grid

  Scenario: Disable related products functionality
    Given I proceed as the Admin
    When go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should not see "Related Products"
    And I should not see "RelatedProductsViewGrid" grid

  Scenario: Limit should be restricted
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 2     |
    And I click "Save settings"
    When go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |
    Then "Select products" button is disabled
    And I should see "Limit of related items has been reached"
    And I click "Cancel" in modal window

  Scenario: Check related grid view after related product title has been updated
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 25    |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU2 |
    And I click "Select products"
    And I save and close form
    And I should see "Product has been saved" flash message
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    When go to Products/ Products
    And I click Edit PSKU2 in grid
    And I fill "ProductForm" with:
      | SKU  | PSKU22            |
      | Name | Product 2 updated |
    And I save and close form
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should see following "RelatedProductsEditGrid" grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |

  Scenario: Check if relation is saved after disable/enable related items feature
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should not see "RelatedProductsViewGrid" grid
    When go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | true  |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should see following grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |

  Scenario: Verify relation is removed in case when related product has been removed
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PSKU3 |
    And I click "Select products"
    And I save and close form
    And I should see "Product has been saved" flash message
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |
    And go to Products/ Products
    And I click Edit "PSKU22" in grid
    When I click "Delete"
    And I confirm deletion
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |

  Scenario: Related items should not be editable on view if user has no permission
    Given user has following permissions
      | Assign | Product | None   |
      | Create | Product | None   |
      | Delete | Product | None   |
      | Edit   | Product | None   |
      | View   | Product | Global |
    And user has following entity permissions enabled
      | [Related Products] Edit Related Products |
    Then I proceed as the Manager
    And I am on dashboard
    And go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should see "Related Items"
    And I should not see an "ProductViewRelatedItemQuickEdit" element
