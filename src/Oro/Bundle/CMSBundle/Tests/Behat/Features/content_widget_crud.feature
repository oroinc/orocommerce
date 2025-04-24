@regression
@behat-test-env
@ticket-BB-17552
@fixture-OroProductBundle:featured_products.yml

Feature: Content Widget CRUD
  In order to manage content widgets from backoffice
  As an administrator
  I want to be able to create, view, update, delete content widgets

  Scenario: Create content widget
    Given I login as administrator
    And go to Marketing/ Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Type | Product Mini-Block |
      | Name | test"\%$#test      |
    Then I should see validation errors:
      | Name | This value should contain only alphabetic symbols, underscore, hyphen and numbers. |

  Scenario: Create content widget
    Given fill "Content Widget Form" with:
      | Name            | product_mini_block  |
      | Description     | product_mini_block1 |
      | Product         | Product 1           |
      | Show Prices     | true                |
      | Show Add Button | true                |
    When I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Product Mini-Bloc"
    And I should see Content Widget with:
      | Name            | product_mini_block  |
      | Description     | product_mini_block1 |
      | Product         | Product 1           |
      | Show Prices     | Yes                 |
      | Show Add Button | Yes                 |

  Scenario: Update content widget
    When I click "Edit"
    And fill "Content Widget Form" with:
      | Description     | product_mini_block2 |
      | Show Prices     | false               |
      | Show Add Button | false               |
    And I save and close form
    And I should see "Type: Product Mini-Bloc"
    And I should see Content Widget with:
      | Name            | product_mini_block  |
      | Description     | product_mini_block2 |
      | Show Prices     | No                  |
      | Show Add Button | No                  |

  Scenario: Check content widgets datagrid
    When go to Marketing/ Content Widgets
    Then there is 13 records in grid
    And I should see following grid:
      | Name               | Description         | Type               | Layout |
      | product_mini_block | product_mini_block2 | Product Mini-Block |        |
    And It should be 6 columns in grid
    And I should see "Created At" column in grid
    And I should see "Updated At" column in grid
    When click "Grid Settings"
    And I click "Filters" tab
    Then I should see following filters in the grid settings in exact order:
      | Name        |
      | Description |
      | Type        |
      | Created At  |
      | Updated At  |
