@fixture-OroProductBundle:product_collections_individual_products.yml
Feature: Product collection individual products
  In order to simplify product collection creation
  As an Administrator
  I want to have ability of adding individual products into Product Collection variant

  Scenario: Add product collection
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    Then I should see 1 elements "Product Collection Variant Label"

  Scenario: Add manually products from All Added tab
    When I click "All Added"
    And I click "Add Button"
    Then I should see "Add Products"
    When I check PSKU5 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |

  Scenario: User is able to add product manually from Manual Added tab
    When I click "Manually Added"
    And I click "Add Button"
    Then I should see "Add Products"
    When I check PSKU4 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |

  Scenario: User is able to exclude product manually from Excluded tab
    When I click "Excluded"
    And I click "Add Button"
    Then I should see "Add Products"
    When I check PSKU5 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |

  Scenario: User is add already excluded product from Manually Added tab
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
    When I click "Add Button"
    When I check PSKU5 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |

  Scenario: Added Product disappears from Excluded tab
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Remove product from manual tab with help of Reset to Default grid action
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |
    When I click Reset to Default on PSKU5 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |

  Scenario: Add several products from several pages from Manually Added tab
    When I click "Add Button"
    Then I should see "Add Products"
    When I check PSKU9 record in "Add Products Popup" grid
    And I check PSKU7 record in "Add Products Popup" grid
    And I press next page button in grid "Add Products Popup"
    And I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |
      | PSKU7 | Product 7 |
      | PSKU9 | Product 9 |

  Scenario: Exclude product from All Added grid
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |
      | PSKU7 | Product 7 |
      | PSKU9 | Product 9 |
    When I click Exclude on PSKU7 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |
      | PSKU9 | Product 9 |
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU7 | Product 7 |

  Scenario: Remove product from Manually Added
    When I click "Manually Added"
    And I click Reset to Default on PSKU9 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |

  Scenario: Excluded products appear in Manually Added popup but already added don't
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |
    When I click "Add Button"
    Then I should see following "Add Products Popup" grid:
      | SKU    | NAME       |
      | PSKU12 | Product 12 |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU9  | Product 9  |
      | PSKU8  | Product 8  |
      | PSKU7  | Product 7  |
      | PSKU6  | Product 6  |
      | PSKU5  | Product 5  |
      | PSKU3  | Product 3  |
      | PSKU2  | Product 2  |
