@regression
@ticket-BB-14600
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Configurable product in quick order form
  As a user I should check that configurable products are not available in quick order form autocomplete
  I need to create configurable product with and without simple products
  And check their availability in quick order form autocomplete

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Create configurable product with correct data (Product Attribute, Product Family)
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Product Attributes
    And I click "Import file"
    And I upload "configurable_product_in_quick_order_form_attributes.csv" file to "ShoppingListImportFileField"
    And I click "Import file"
    And I reload the page
    And I confirm schema update

    And go to Products/ Product Families
    When I click "Create Product Family"
    And fill "Product Family Form" with:
      | Code       | tshirt_family |
      | Label      | Tshirts       |
      | Enabled    | True          |
    And click "Add"
    And fill "Attributes Group Form" with:
      |Attribute Groups Label1     |Product Prices  |
      |Attribute Groups Visible1   |true            |
      |Attribute Groups Attributes1|[Product prices]|
    And click "Add"
    And fill "Attributes Group Form" with:
      |Attribute Groups Label2     |Inventory         |
      |Attribute Groups Visible2   |true              |
      |Attribute Groups Attributes2|[Inventory Status]|
    And click "Add"
    And fill "Attributes Group Form" with:
      |Attribute Groups Label3     |Images  |
      |Attribute Groups Visible3   |true    |
      |Attribute Groups Attributes3|[Images]|
    And click "Add"
    And fill "Attributes Group Form" with:
      |Attribute Groups Label4     |SEO                              |
      |Attribute Groups Visible4   |true                             |
      |Attribute Groups Attributes4|[Meta keywords, Meta description]|
    And click "Add"
    And fill "Attributes Group Form" with:
      |Attribute Groups Label5     |Attribute Family|
      |Attribute Groups Visible5   |true            |
      |Attribute Groups Attributes5|[Color, Size]   |
    And save and close form
    Then should see "Product Family was successfully saved" flash message

    And go to Products/ Master Catalog
    And click "Create Category"
    And fill "Create Category Form" with:
      |Title              |Shirts|
      |Inventory Threshold|0     |
    And click "Save"

  Scenario: Prepare products
    And I go to Products / Products
    And I click "Import file"
    And I upload "configurable_product_in_quick_order_form_products.csv" file to "ShoppingListImportFileField"
    And I click "Import file"

  Scenario: Prepare product prices
    And I go to Sales/ Price Lists
    And click view "Default Price List" in grid
    And I click "Import file"
    And I upload "configurable_product_in_quick_order_form_prices.csv" file to "ShoppingListImportFileField"
    And I click "Import file"

  Scenario: Check validation for configurable products and simple products from the configurable product
    Given I proceed as the User
    And I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | Shirt_Sku |
    And I wait for products to load
    Then I should see text matching "Item number cannot be found"
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | EmptyShirt_Sku |
    And I wait for products to load
    Then I should see text matching "Item number cannot be found"
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | Black_Shirt_L_sku |
    And I wait for products to load
    Then I should see text matching "Item number cannot be found"
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | Shirt_Sku 1 |
    And I click "Verify Order"
    Then I should see text matching "Item number cannot be found."
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | EmptyShirt_Sku 1 |
    And I click "Verify Order"
    Then I should see text matching "Item number cannot be found."
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | White_Shirt_L_sku 1 |
    And I click "Verify Order"
    Then I should see text matching "Item number cannot be found."
