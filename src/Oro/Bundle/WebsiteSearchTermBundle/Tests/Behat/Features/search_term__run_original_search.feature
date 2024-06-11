@feature-BB-21439
@fixture-OroProductBundle:products_grid_frontend.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - Run Original Search

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    And uncheck "Use default" for "Enable Search Terms Management" field
    And I check "Enable Search Terms Management"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Fill Search Term fields
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I click "Add"
    And I fill "Search Term Form" with:
      | Phrases                      | [Product]        |
      | Restriction 1 Customer Group |                  |
      | Restriction 2 Customer Group | AmandaRColeGroup |
    Then I should see an "Run Original Search" element
    And I should see an "Run Original Search (Restriction 2)" element

  Scenario: Run Original Search for Product
    When I click "Run Original Search Dropdown"
    Then I should see an "Run Original Search for Product" element
    When I click "Run Original Search for Product"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU1  | Product 1  | In Stock         |
      | PSKU10 | Product 10 | In Stock         |
      | PSKU11 | Product 11 | In Stock         |
      | PSKU12 | Product 12 | In Stock         |
      | PSKU13 | Product 13 | In Stock         |
      | PSKU14 | Product 14 | In Stock         |
      | PSKU15 | Product 15 | In Stock         |
      | PSKU16 | Product 16 | In Stock         |
      | PSKU17 | Product 17 | In Stock         |
      | PSKU18 | Product 18 | In Stock         |
      | PSKU19 | Product 19 | In Stock         |
      | PSKU2  | Product 2  | In Stock         |
      | PSKU20 | Product 20 | In Stock         |
      | PSKU3  | Product 3  | In Stock         |
      | PSKU4  | Product 4  | In Stock         |
      | PSKU5  | Product 5  | In Stock         |
      | PSKU7  | Product 7  | Out of Stock     |
      | PSKU8  | Product 8  | In Stock         |
      | PSKU9  | Product 9  | In Stock         |

  Scenario: Filter by SKU
    When filter SKU as is equal to "PSKU13"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU13 | Product 13 | In Stock         |
    And there is one record in grid

    When filter SKU as contains "SKU13"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU13 | Product 13 | In Stock         |
    And there is one record in grid

    When filter SKU as Does Not Contain "1"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU2  | Product 2  | In Stock         |
      | PSKU20 | Product 20 | In Stock         |
      | PSKU3  | Product 3  | In Stock         |
      | PSKU4  | Product 4  | In Stock         |
      | PSKU5  | Product 5  | In Stock         |
      | PSKU7  | Product 7  | Out of Stock     |
      | PSKU8  | Product 8  | In Stock         |
      | PSKU9  | Product 9  | In Stock         |
    And there is 8 records in grid

  Scenario: Filter by Name
    When I reset "SKU" filter
    And filter Name as is equal to "Product 13"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU13 | Product 13 | In Stock         |
    And there is one record in grid

    When filter Name as contains "13"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU13 | Product 13 | In Stock         |
    And there is one record in grid

    When filter Name as Does Not Contain "1"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU2  | Product 2  | In Stock         |
      | PSKU20 | Product 20 | In Stock         |
      | PSKU3  | Product 3  | In Stock         |
      | PSKU4  | Product 4  | In Stock         |
      | PSKU5  | Product 5  | In Stock         |
      | PSKU7  | Product 7  | Out of Stock     |
      | PSKU8  | Product 8  | In Stock         |
      | PSKU9  | Product 9  | In Stock         |
    And there is 8 records in grid

  Scenario: Filter by Inventory Status
    When I reset "Name" filter
    And I check "In Stock" in "Inventory Status: All" filter strictly
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU1  | Product 1  | In Stock         |
      | PSKU10 | Product 10 | In Stock         |
      | PSKU11 | Product 11 | In Stock         |
      | PSKU12 | Product 12 | In Stock         |
      | PSKU13 | Product 13 | In Stock         |
      | PSKU14 | Product 14 | In Stock         |
      | PSKU15 | Product 15 | In Stock         |
      | PSKU16 | Product 16 | In Stock         |
      | PSKU17 | Product 17 | In Stock         |
      | PSKU18 | Product 18 | In Stock         |
      | PSKU19 | Product 19 | In Stock         |
      | PSKU2  | Product 2  | In Stock         |
      | PSKU20 | Product 20 | In Stock         |
      | PSKU3  | Product 3  | In Stock         |
      | PSKU4  | Product 4  | In Stock         |
      | PSKU5  | Product 5  | In Stock         |
      | PSKU8  | Product 8  | In Stock         |
      | PSKU9  | Product 9  | In Stock         |
    And there is 18 records in grid

    When I reset "Inventory Status: In Stock" filter
    And I check "Out of Stock" in "Inventory Status: All" filter strictly
    Then I should see following grid:
      | SKU   | NAME      | INVENTORY STATUS |
      | PSKU7 | Product 7 | Out of Stock     |
    And there is one record in grid
