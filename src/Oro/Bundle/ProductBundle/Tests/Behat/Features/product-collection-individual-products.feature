@fixture-product_collections.yml
Feature:
  In order to add more than one product by some criteria into the content nodes
  As an Administrator
  I want to have ability of adding Product Collection variant

  Scenario: Add condition on SKU using Advanced Filter
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
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
    And I click on "Preview"
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU12 | Product 12 |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU1  | Product 1  |

  Scenario: Excluded Product disappears from All Added when filters applied
    When I click "Excluded"
    And I click "Add Button"
    Then I should see "Add Products"
    And I click on PSKU12 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU1  | Product 1  |

  Scenario: After remove excluded All Added tab shows all filtered products
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |
    When I click Remove on PSKU12 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME        |
      | PSKU12 | Product 12 |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU1  | Product 1  |

  Scenario: No records found if grid filter is empty and no Manually added products exist
    When I click on "Remove Filter Button"
    And I click on "Preview"
    Then I should see "No records found"

  Scenario: Add manually products from All Added tab
    When I click "All Added"
    And I click "Add Button"
    Then I should see "Add Products"
    And I click on PSKU5 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |

  Scenario: User is able to add product manually from Manual Added tab
    When I click "Content Variants"
    And I click "Manually Added"
    And I click "Add Button"
    Then I should see "Add Products"
    And I click on PSKU4 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |

  Scenario: User is able to exclude product manually from Excluded tab
    When I click "Excluded"
    And I click "Add Button"
    Then I should see "Add Products"
    When I click on PSKU5 in grid "Add Products Popup Grid"
    And I click on PSKU8 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU8 | Product 8 |
      | PSKU5 | Product 5 |
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |

  Scenario: Remove product from excluded
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU8 | Product 8 |
    And I click Remove on PSKU8 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |

  Scenario: User is add already excluded product from Manually Added tab
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
    When I click "Add Button"
    And I click on PSKU5 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |

  Scenario: Added Product disappears from Excluded tab
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Remove product from manual tab with help of Reset to Default grid action
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |
    When I click Reset to Default on PSKU5 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |

  Scenario: Add several products from several pages from Manually Added tab
    When I click "Add Button"
    Then I should see "Add Products"
    And I click on PSKU9 in grid "Add Products Popup Grid"
    And I click on PSKU7 in grid "Add Products Popup Grid"
    And I press next page button in grid "Add Products Popup Grid"
    And I click on PSKU1 in grid "Add Products Popup Grid"
    And I click "Add" in modal window
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU9 | Product 9 |
      | PSKU7 | Product 7 |
      | PSKU4 | Product 4 |
      | PSKU1 | Product 1 |

  Scenario: Exclude product from All Added grid
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU9 | Product 9 |
      | PSKU7 | Product 7 |
      | PSKU4 | Product 4 |
      | PSKU1 | Product 1 |
    And I click Exclude on PSKU7 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU9 | Product 9 |
      | PSKU4 | Product 4 |
      | PSKU1 | Product 1 |
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU7 | Product 7 |

  Scenario: Already excluded products don't appear in Excluded popup
    When I click "Add Button"
    Then I should see "Add Products"
    And I should see following "Add Products Popup Grid" grid:
      | SKU    | NAME       |
      | PSKU12 | Product 12 |
      | PSKU11 | Product 11 |
      | PSKU10 | Product 10 |
      | PSKU9  | Product 9  |
      | PSKU8  | Product 8  |
      | PSKU6  | Product 6  |
      | PSKU5  | Product 5  |
      | PSKU4  | Product 4  |
      | PSKU3  | Product 3  |
      | PSKU2  | Product 2  |
    And I click "Cancel" in modal window

  Scenario: Remove product from Manually Added
    When I click "Manually Added"
    And I click Reset to Default on PSKU9 in grid "Active Grid"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU1 | Product 1 |

  Scenario: Excluded products appear in Manually Added popup but already added don't
    When I click "Manually Added"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU1 | Product 1 |
    And I click "Add Button"
    Then I should see following "Add Products Popup Grid" grid:
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

