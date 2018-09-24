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
    And go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label  |
      | Black  |
      | White  |
    And save and close form
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label  |
      | L      |
      | M      |
    When I save and close form
    And click update schema
    Then should see Schema updated flash message

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

    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Simple |
      |Product Family|Tshirts|
    And click "Shirts"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |Black_Shirt_M_sku|
      |Name            |Black Shirt      |
      |Status          |Enable           |
      |Unit Of Quantity|item             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |black_shirt.jpg|
      |Main Image      |true           |
      |Listing Image   |true           |
      |Additional Image|true           |
      |Color           |Black          |
      |Size            |M              |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 10                 |
      | Currency       | $                  |
    And save and close form
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Simple|
      |Product Family|Tshirts|
    And click "Shirts"
      |Category|Shirts|
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |Black_Shirt_L_sku|
      |Name            |Black Shirt      |
      |Status          |Enable           |
      |Unit Of Quantity|item             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |black_shirt.jpg|
      |Main Image      |true           |
      |Listing Image   |true           |
      |Additional Image|true           |
      |Color           |Black          |
      |Size            |L              |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 8                  |
      | Currency       | $                  |
    And save and close form
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Simple|
      |Product Family|Tshirts|
    And click "Shirts"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |White_Shirt_M_sku|
      |Name            |White Shirt      |
      |Status          |Enable           |
      |Unit Of Quantity|item             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |white_shirt.jpg|
      |Main Image      |true           |
      |Listing Image   |true           |
      |Additional Image|true           |
      |Color           |White          |
      |Size            |M              |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 12                 |
      | Currency       | $                  |
    And save and close form
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Simple|
      |Product Family|Tshirts|
    And click "Shirts"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |White_Shirt_L_sku|
      |Name            |White Shirt      |
      |Status          |Enable           |
      |Unit Of Quantity|item             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |white_shirt.jpg|
      |Main Image      |true           |
      |Listing Image   |true           |
      |Additional Image|true           |
      |Color           |White          |
      |Size            |L              |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 13                 |
      | Currency       | $                  |
    And save and close form

    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Configurable|
      |Product Family|Tshirts     |
    And click "Shirts"
    And click "Continue"
    And fill "Create Product Form" with:
      |Name                         |ConfigurableShirt|
      |SKU                          |Shirt_Sku        |
      |Status                       |Enable           |
      |Unit Of Quantity             |item             |
      |Configurable Attributes Color|true             |
      |Configurable Attributes Size |true             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |uni_shirt.jpg|
      |Main Image      |true         |
      |Listing Image   |true         |
      |Additional Image|true         |
    And save and close form
    And click "Edit Product"
    And click on Black_Shirt_L_sku in grid
    And click on Black_Shirt_M_sku in grid
    And click on White_Shirt_L_sku in grid
    And click on White_Shirt_M_sku in grid
    And save and close form
    Then should see "Product has been saved" flash message

    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type          |Configurable|
      |Product Family|Tshirts     |
    And click "Shirts"
    And click "Continue"
    And fill "Create Product Form" with:
      |Name                         |EmptyConfigurableShirt|
      |SKU                          |EmptyShirt_Sku        |
      |Status                       |Enable           |
      |Unit Of Quantity             |item             |
      |Configurable Attributes Color|true             |
      |Configurable Attributes Size |true             |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |uni_shirt.jpg|
      |Main Image      |true         |
      |Listing Image   |true         |
      |Additional Image|true         |
    And save and close form
    Then should see "Product has been saved" flash message

  Scenario: Check validation for configurable products and simple products from the configurable product
    Given I proceed as the User
    And I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | Shirt_Sku |
    And I wait for products to load
    Then I should see text matching "Item Number Cannot Be Found"
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | EmptyShirt_Sku |
    And I wait for products to load
    Then I should see text matching "Item Number Cannot Be Found"
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | Black_Shirt_L_sku |
    And I wait for products to load
    Then I should see text matching "Item Number Cannot Be Found"
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | Shirt_Sku 1 |
    And I click "Verify Order"
    Then I should see that "Quick Add Copy Paste Validation" contains "Some of the products SKUs or units you have provided were not found. Correct them and try again."
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | EmptyShirt_Sku 1 |
    And I click "Verify Order"
    Then I should see that "Quick Add Copy Paste Validation" contains "Some of the products SKUs or units you have provided were not found. Correct them and try again."
    When I click "Quick Order Form"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | White_Shirt_L_sku 1 |
    And I click "Verify Order"
    Then I should see that "Quick Add Copy Paste Validation" contains "Some of the products SKUs or units you have provided were not found. Correct them and try again."
