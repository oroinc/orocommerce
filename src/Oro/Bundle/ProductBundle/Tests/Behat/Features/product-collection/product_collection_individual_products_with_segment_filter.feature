@fixture-OroProductBundle:product_collections_individual_products.yml
Feature: Product collection individual products with segment filter
  In order to simplify product collection creation together with filters using
  As an Administrator
  I want to have ability of adding individual products into Product Collection variant

  Scenario: Add condition on SKU using Advanced Filter
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Remove Variant Button"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    Then I should see 1 elements "Product Collection Variant Label"
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU1" in "value"
    And I click on "Preview Results"
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
      | PSKU12 | Product 12 |

  Scenario: Excluded Product disappears from All Added when filters applied
    When I click "Excluded"
    And I click "Add Button"
    Then I should see "Add Products"
    And I check PSKU12 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |

  Scenario: Already excluded products don't appear in Excluded popup
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |
    When I click "Add Button"
    Then I should see "Add Products"
    And I should see following "Add Products Popup" grid:
      | SKU    | NAME       |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU9  | Product 9  |
      | PSKU8  | Product 8  |
      | PSKU7  | Product 7  |
      | PSKU6  | Product 6  |
      | PSKU5  | Product 5  |
      | PSKU4  | Product 4  |
      | PSKU3  | Product 3  |
      | PSKU2  | Product 2  |
    When I click "Cancel" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |

  Scenario: Exclude product from All Added grid when filter is applied
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
    And I click Exclude on PSKU10 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU11 | Product 11 |

  Scenario: Save collection and check All Added products are valid
    When I save form
    And I click on "First Content Variant Expand Button"
    And I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU11 | Product 11 |

  Scenario: Remove product from Excluded tab with help of Remove grid action
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU10 | Product 10 |
      | PSKU12 | Product 12 |
    And I click Remove on PSKU12 in grid "Active Grid"
    And I click Remove on PSKU10 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: After remove excluded All Added tab shows all filtered products
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
      | PSKU12 | Product 12 |

  Scenario: Adding product manually is possible when filter is not complete
    When I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "Inventory Status"
    When I click "All Added"
    And I click Exclude on PSKU11 in grid "Active Grid"
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU12 | Product 12 |

  Scenario: No records found if grid filter is empty and no Manually added products exist
    When I click on "Remove Filter Button"
    And I click on "Remove Filter Button"
    And I click on "Preview Results"
    Then I should see "There are no products"
