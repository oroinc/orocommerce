@fixture-OroCatalogBundle:category_tree_with_products.yml
Feature: Check sub-categories and not categorized products values
  In order to have possibility filter products by categories and view products without category
  As site administrator
  I need to be able filter products by categories and view products without category

  Scenario: View all products without filters
    Given I login as administrator
    When I go to Products/Products
    Then I should see following grid:
      | Name      |
      | Product 4 |
      | Product 3 |
      | Product 2 |
      | Product 1 |

  Scenario: Check include sub category without parent category
    Given I go to Products/Products
    And I check "Include SubCategories" element
    Then I should see following grid:
      | Name      |
      | Product 4 |
      | Product 3 |
      | Product 2 |
      | Product 1 |

  Scenario: Filter product by parent category
    Given I go to Products/Products
    And I click "Retail Supplies"
    Then I should see following grid:
      | Name      |
      | Product 2 |

  Scenario: Filter product by parent category with children categories
    Given I go to Products/Products
    And I click "Retail Supplies"
    And I check "Include SubCategories" element
    Then I should see following grid:
      | Name      |
      | Product 3 |
      | Product 2 |

  Scenario: Filter not categorized products
    Given I go to Products/Products
    And I check "Include Not Categorized Products" element
    Then I should see following grid:
      | Name      |
      | Product 4 |

  Scenario: Filter product by parent category with children and with not categorized products
    Given I go to Products/Products
    And I click "Retail Supplies"
    And I check "Include SubCategories" element
    And I check "Include Not Categorized Products" element
    Then I should see following grid:
      | Name      |
      | Product 4 |
      | Product 3 |
      | Product 2 |
