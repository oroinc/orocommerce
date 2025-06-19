@ticket-BB-22393
@regression
@fixture-OroProductBundle:product_field_permissions.yml

Feature: Check product fields without permissions

  Scenario: Enable Field ACL for product entity
    Given I login as administrator
    And go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    When I click Edit Product in grid
    And check "Field Level ACL"
    And save and close form
    Then I should see "Entity saved" flash message

  Scenario: Add product attributes
    Given I go to Products/ Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And I fill form with:
      | Searchable           | Yes             |
      | Filterable           | Yes             |
      | Show on view         | Yes             |
      | Add to Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
    And set Options with:
      | Label |
      | Black |
    And save and close form

  Scenario: Update product family
    Given I go to Products/ Product Families
    And click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I go to Products/Products
    When I click Edit 1GB81 in grid
    And fill in product attribute "Color" with "Black"
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check columns, filters and sorters on product grid
    Given I go to Products/Products
    When I click "Grid Settings"
    Then I should see following columns in the grid settings:
      | SKU              |
      | Type             |
      | Image            |
      | Name             |
      | Product Family   |
      | Status           |
      | Inventory Status |
      | New Arrival      |
      | Tax Code         |
      | Shipping Options |
      | Color            |
      | Created At       |
      | Updated At       |
    And should see following filters in the grid settings:
      | SKU              |
      | Type             |
      | Name             |
      | Product Family   |
      | Status           |
      | Inventory Status |
      | New Arrival      |
      | Tax Code         |
      | Color            |
      | Created At       |
      | Updated At       |
    And should see following grid:
      | SKU   | Name    | Product Family | Status  | Inventory status |
      | 1GB81 | Product | Default        | Enabled | In Stock         |

  Scenario: Change product fields permissions
    Given I go to System/User Management/Roles
    And filter Label as is equal to "Administrator"
    And click Edit Administrator in grid
    When I expand "Product" permissions in "Entity" section
    And select following field permissions:
      | Availability Date            | View:None | Create:None | Edit:None |
      | Backorders                   | View:None |             | Edit:None |
      | Brand                        | View:None | Create:None | Edit:None |
      | Category                     | View:None | Create:None | Edit:None |
      | Category sort order          | View:None | Create:None | Edit:None |
      | Color                        | View:None | Create:None | Edit:None |
      | Configurable Attributes      | View:None | Create:None | Edit:None |
      | Created At                   | View:None | Create:None | Edit:None |
      | Decrement Inventory          | View:None | Create:None | Edit:None |
      | Description                  | View:None | Create:None | Edit:None |
      | Highlight Low Inventory      | View:None |             | Edit:None |
      | Images                       | View:None | Create:None | Edit:None |
      | Inventory Status             | View:None |             | Edit:None |
      | Inventory Threshold          | View:None | Create:None | Edit:None |
      | Is Featured                  | View:None |             | Edit:None |
      | Low Inventory Threshold      | View:None | Create:None | Edit:None |
      | Managed Inventory            | View:None |             | Edit:None |
      | Maximum Quantity To Order    | View:None | Create:None | Edit:None |
      | Meta description             | View:None | Create:None | Edit:None |
      | Meta keywords                | View:None | Create:None | Edit:None |
      | Meta title                   | View:None | Create:None | Edit:None |
      | Minimum Quantity To Order    | View:None | Create:None | Edit:None |
      | Name                         | Edit:None |             |           |
      | New Arrival                  | View:None |             | Edit:None |
      | Organization                 | View:None | Create:None | Edit:None |
      | Owner                        | View:None | Create:None | Edit:None |
      | Page Template                | View:None |             | Edit:None |
      | Parent Product Variant Links | View:None | Create:None | Edit:None |
      | Product Family               | View:None |             | Edit:None |
      | Product Tax Code             | View:None | Create:None | Edit:None |
      | Product Variant Links        | View:None | Create:None | Edit:None |
      | Product prices               | View:None | Create:None | Edit:None |
      | SKU                          | Edit:None |             |           |
      | Short Description            | View:None | Create:None | Edit:None |
      | Slugs                        | View:None | Create:None | Edit:None |
      | Status                       | View:None |             | Edit:None |
      | Type                         | View:None |             | Edit:None |
      | URL Slug                     | View:None | Create:None | Edit:None |
      | Unit Precisions              | View:None |             | Edit:None |
      | Unit of Quantity             | View:None |             | Edit:None |
      | Upcoming                     | View:None |             | Edit:None |
      | Updated At                   | View:None | Create:None | Edit:None |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Check columns, filters and sorters on product grid without permissions
    Given I go to Products/Products
    When I click "Grid Settings"
    # The 'show restricted' option works only with the EDIT and CREATE permissions
    Then I should see following columns in the grid settings:
      | SKU  |
      | Name |
    And should not see following columns in the grid settings:
      | Type             |
      | Image            |
      | Product Family   |
      | Status           |
      | Inventory Status |
      | New Arrival      |
      | Tax Code         |
      | Shipping Options |
      | Color            |
      | Created At       |
      | Updated At       |
    And should see following filters in the grid settings:
      | SKU  |
      | Name |
    And should not see following filters in the grid settings:
      | Type             |
      | Product Family   |
      | Status           |
      | Inventory Status |
      | New Arrival      |
      | Tax Code         |
      | Color            |
      | Created At       |
      | Updated At       |
    And should see following grid:
      | SKU   | Name    |
      | 1GB81 | Product |
    # 3 rows - Select-all, SKU, Name
    And It should be 2 columns in grid

  Scenario: Check product view page
    Given I go to Products/ Products
    When I click View 1GB81 in grid
    Then I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Activity"
    And I should see that "Navbar" contains "Additional"
    And I should see that "Navbar" contains "Related Items"
    And I should see that "Navbar" does not contain "Short Description"
    And I should see that "Navbar" does not contain "Description"
    And I should see that "Navbar" does not contain "Images"
    And I should see that "Navbar" does not contain "Design"
    And I should see that "Navbar" does not contain "Price Attributes"
    And I should see that "Navbar" does not contain "Product Prices"
    And I should see that "Navbar" does not contain "Shipping Options"

  Scenario: Check product edit page
    Given I go to Products/ Products
    When I click Edit 1GB81 in grid
    Then I should see that "Navbar" contains "Related Items"
    And I should see that "Navbar" does not contain "General"
    And I should see that "Navbar" does not contain "Short Description"
    And I should see that "Navbar" does not contain "Description"
    And I should see that "Navbar" does not contain "Images"
    And I should see that "Navbar" does not contain "Design"
    And I should see that "Navbar" does not contain "Master Catalog"
    And I should see that "Navbar" does not contain "Inventory"
    And I should see that "Navbar" does not contain "Product Prices"
    And I should see that "Navbar" does not contain "SEO"
    And I should see that "Navbar" does not contain "Shipping Options"

  Scenario: Check product create pages
    Given I go to Products/ Products
    And click "Create Product"
    Then I should see that "Navbar" does not contain "Master Catalog"
    And I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Product Family"
    And I click "Continue"
    And I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Design"
    And I should see that "Navbar" contains "Inventory"
    And I should see that "Navbar" contains "Shipping Options"
    And I should see that "Navbar" does not contain "Short Description"
    And I should see that "Navbar" does not contain "Description"
    And I should see that "Navbar" does not contain "Images"
    And I should see that "Navbar" does not contain "Product Prices"
    When I fill "ProductForm" with:
      | Sku  | ORO_PRODUCT_1 |
      | Name | ORO_PRODUCT_1 |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set "Show restricted" options for product entity
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    And click Edit Product in grid
    And check "Show restricted"
    When I save and close form
    Then I should see "Entity saved" flash message

  Scenario: Check product view page
    Given I go to Products/ Products
    When I click View 1GB81 in grid
    Then I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Activity"
    And I should see that "Navbar" contains "Additional"
    And I should see that "Navbar" contains "Related Items"
    And I should see that "Navbar" does not contain "Short Description"
    And I should see that "Navbar" does not contain "Description"
    And I should see that "Navbar" does not contain "Images"
    And I should see that "Navbar" does not contain "Design"
    And I should see that "Navbar" does not contain "Price Attributes"
    And I should see that "Navbar" does not contain "Product Prices"
    And I should see that "Navbar" does not contain "Shipping Options"

  Scenario: Check product edit page
    Given I go to Products/ Products
    When I click Edit 1GB81 in grid
    Then I should see that "Navbar" contains "Related Items"
    And I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Short Description"
    And I should see that "Navbar" contains "Description"
    And I should see that "Navbar" contains "Images"
    And I should see that "Navbar" contains "Design"
    And I should see that "Navbar" contains "Master Catalog"
    And I should see that "Navbar" contains "Inventory"
    And I should see that "Navbar" contains "Product Prices"
    And I should see that "Navbar" contains "SEO"
    And I should see that "Navbar" contains "Shipping Options"

  Scenario: Check product create pages
    Given I go to Products/ Products
    And click "Create Product"
    Then I should see that "Navbar" contains "Master Catalog"
    And I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Product Family"
    And I click "Continue"
    And I should see that "Navbar" contains "General"
    And I should see that "Navbar" contains "Design"
    And I should see that "Navbar" contains "Inventory"
    And I should see that "Navbar" contains "Shipping Options"
    And I should see that "Navbar" contains "Short Description"
    And I should see that "Navbar" contains "Description"
    And I should see that "Navbar" contains "Images"
    And I should see that "Navbar" contains "Product Prices"
    When I fill "ProductForm" with:
      | Sku  | ORO_PRODUCT_2 |
      | Name | ORO_PRODUCT_2 |
    And save and close form
    Then I should see "Product has been saved" flash message
