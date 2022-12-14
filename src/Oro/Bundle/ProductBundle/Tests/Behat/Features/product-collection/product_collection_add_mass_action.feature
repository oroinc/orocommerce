@regression
@fixture-OroProductBundle:product_collections_individual_products.yml
Feature: Product collection add mass action
  In order to add more than one product by some criteria into the content nodes
  As an Administrator
  I want to have ability of adding Product Collection variant

  Scenario: Add Product Collection variant
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"

  Scenario: Check "add" mass action for Excluded tab popup grid when no products selected
    When I click "Excluded"
    And I click on "Add Button"
    When I click Add mass action in "Add Products Popup" grid
    Then I should see "Please, select items to perform mass action." in the "UiDialog" element
    When I click "Cancel" in modal window
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Check Add button for Excluded tab popup when no products selected
    When I click on "Add Button"
    Then I should see "Add Products"
    When I click "Add" in "UiDialog ActionPanel" element
    Then I should see "Please, select items to perform mass action." in the "UiDialog" element
    When I click "Cancel" in modal window
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Check Add button for Excluded tab popup when several products from several pages selected
    When I click on "Add Button"
    Then I should see "Add Products"
    When I check PSKU11 record in "Add Products Popup" grid
    And I check PSKU4 record in "Add Products Popup" grid
    And I press next page button in grid "Add Products Popup"
    And I check PSKU2 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU2  | Product 2  |
      | PSKU4  | Product 4  |
      | PSKU11 | Product 11 |

  Scenario: Check "add" mass action for Manually Added tab popup grid when no products selected
    When I click "Manually Added"
    And I click on "Add Button"
    Then I should see "Add Products"
    When I click Add mass action in "Add Products Popup" grid
    Then I should see "Please, select items to perform mass action." in the "UiDialog" element
    When I click "Cancel" in modal window
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Check Add button for Manually Added tab popup when no products selected
    When I click on "Add Button"
    Then I should see "Add Products"
    When I click "Add" in "UiDialog ActionPanel" element
    Then I should see "Please, select items to perform mass action." in the "UiDialog" element
    When I click "Cancel" in modal window
    Then I should see following "Active Grid" grid:
      | SKU | NAME |

  Scenario: Check Add button for Manually Added tab popup when several products from several pages selected
    When I click on "Add Button"
    Then I should see "Add Products"
    When I check PSKU12 record in "Add Products Popup" grid
    And I check PSKU7 record in "Add Products Popup" grid
    And I press next page button in grid "Add Products Popup"
    And I check PSKU1 record in "Add Products Popup" grid
    And I click Add mass action in "Add Products Popup" grid
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU7  | Product 7  |
      | PSKU12 | Product 12 |
