@regression
@smoke-community-edition-only
Feature: Commerce smoke e2e

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Create Product Tax Code, Customer Tax Code, Create Tax, Create Tax Jurisdiction, Create Tax Rule
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And follow "Commerce/Inventory/Product Options" on configuration sidebar
    And fill "Product Option Form" with:
      |Backorders Default|false|
      |Backorders        |Yes  |
    And click "Save settings"
    And go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      |Enable Guest Shopping List Default|false|
      |Enable Guest Shopping List        |true |
    And click "Save settings"
    And go to System/ Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And fill "Tax Calculation Form" with:
      |Use As Base By Default Use Default|false      |
      |Use As Base By Default            |Destination|
    And click "Save settings"
    And go to Taxes/ Product Tax Codes
    When click "Create Product Tax Code"
    And fill form with:
      |Code       |Phone_Tax_Code                    |
      |Description|Description of Phone Tax Code code|
    And save and close form
    Then should see "Product Tax Code has been saved" flash message
    And go to Taxes/ Customer Tax Codes
    When click "Create Customer Tax Code"
    And fill form with:
      |Code       |New_Customer_Tax_Code                    |
      |Description|Description of New_Customer_Tax_Code code|
    And save and close form
    Then should see "Customer Tax Code has been saved" flash message
    And go to Taxes/ Taxes
    When click "Create Tax"
    And fill form with:
      |Code       |CA |
      |Description|CA |
      |Rate (%)   |9.5|
    And save and close form
    Then should see "Tax has been saved" flash message
    And go to Taxes/ Tax Jurisdictions
    When click "Create Tax Jurisdiction"
    And fill form with:
      |Code       |CA_Jurisdiction|
      |Description|CA_Jurisdiction|
      |Country    |United States  |
      |State      |California     |
    And save and close form
    Then should see "Tax Jurisdiction has been saved" flash message
    And go to Taxes/ Tax Rules
    When click "Create Tax Rule"
    And fill "Tax Rule Form" with:
      |Customer Tax Code|New_Customer_Tax_Code|
      |Product Tax Code |Phone_Tax_Code       |
      |Tax Jurisdiction |CA_Jurisdiction      |
      |Tax              |CA                   |
      |Description      |New Tax Rule         |
    And save and close form
    Then should see "Tax Rule has been saved" flash message

  Scenario: Create category, product in category,  product without category and assign it to the category
    Given I proceed as the Admin
    When go to Products/ Master Catalog
    And click "Create Category"
    And fill "Create Category Form" with:
      |Title              |Phones|
      |Inventory Threshold|0     |
    And click "Save"
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type    |Simple|
    And click "Phones"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |Lenovo_Vibe_sku|
      |Name            |Lenovo Vibe    |
      |Status          |Enable         |
      |Unit Of Quantity|item           |
      |Tax Code        |Phone_Tax_Code |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |Lenovo_vibe.jpg|
      |Main Image      |true           |
      |Listing Image   |true           |
      |Additional Image|true           |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 100                |
      | Currency       | $                  |
    And save and close form
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      |Type    |Simple|
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |Xiaomi_Redmi_3S_sku|
      |Name            |Xiaomi Redmi 3S    |
      |Status          |Enable             |
      |Unit Of Quantity|item               |
      |Tax Code        |Phone_Tax_Code     |
    And I click "Add Image"
    And fill "Create Product Form" with:
      |Product Image   |Xiaomi_Redmi_3S.jpg|
      |Main Image      |true               |
      |Listing Image   |true               |
      |Additional Image|true               |
    And save and close form
    And go to Products/ Products
    And click edit "Xiaomi_Redmi_3S_sku" in grid
    And click "Phones"
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 150                |
      | Currency       | $                  |
    And save and close form
    Then should see "Product has been saved" flash message

  Scenario: Create configurable product with correct data (Product Attribute, Product Family)
    Given I proceed as the Admin
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

  Scenario: Create 2 price lists (1 create through the rules and 2 create through the import)
    Given I proceed as the Admin
    When go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | FirstPriceList |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And click "Add Price Calculation Rules"
    And click "Enter expression unit"
    And fill "Price Calculation Rules Form" with:
      |Price Unit        |pricelist[1].prices.unit|
    And click "Enter expression currency"
    And fill "Price Calculation Rules Form" with:
      |Price Currency    |pricelist[1].prices.currency   |
      |Price for quantity|1                              |
      |Calculate As      |pricelist[1].prices.value * 0.8|
      |Condition         |pricelist[1].prices.value > 1  |
      |Priority          |1                              |
    And save and close form
    Then should see "Price List has been saved" flash message
    When go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | SecondPriceList |
      | Currencies | US Dollar ($)   |
      | Active     | true            |
    And save and close form
    And download "Product Price" Data Template file
    And I fill template with data:
      |Product SKU        |Quantity|Unit Code|Price|Currency|
      |Black_Shirt_L_sku  |10      |item     |7    |USD     |
      |Black_Shirt_M_sku  |10      |item     |9    |USD     |
      |Lenovo_Vibe_sku    |10      |item     |90   |USD     |
      |White_Shirt_L_sku  |10      |item     |12   |USD     |
      |White_Shirt_M_sku  |10      |item     |11   |USD     |
      |Xiaomi_Redmi_3S_sku|10      |item     |135  |USD     |
    And I import file
    And reload the page
    Then should see following grid:
      | Product SKU         | Product name    | Quantity | Unit | Value  | Currency |
      | Black_Shirt_L_sku   | Black Shirt     | 10       | item | 7.00   | USD      |
      | Black_Shirt_M_sku   | Black Shirt     | 10       | item | 9.00   | USD      |
      | Lenovo_Vibe_sku     | Lenovo Vibe     | 10       | item | 90.00  | USD      |
      | White_Shirt_L_sku   | White Shirt     | 10       | item | 12.00  | USD      |
      | White_Shirt_M_sku   | White Shirt     | 10       | item | 11.00  | USD      |
      | Xiaomi_Redmi_3S_sku | Xiaomi Redmi 3S | 10       | item | 135.00 | USD      |

  Scenario: Create customer
    Given I proceed as the Admin
    And go to Sales/ Payment terms
    And click "Create Payment Term"
    And type "net_10" in "Label"
    And save and close form
    When go to Customers/ Customers
    And click "Create Customer"
    And fill "Customer Form" with:
      |Name       |Smoke Customer       |
      |Tax Code   |New_Customer_Tax_Code|
      |Price List |SecondPriceList      |
      |Payment Term|net_10|
    And save and close form
    Then should see "Customer has been saved" flash message

  Scenario: Create customer user from the Admin panel (add address)
    Given I proceed as the Admin
    When go to Customers/ Customer Users
    And click "Create Customer User"
    And fill form with:
      |First Name      |Branda                     |
      |Last Name       |Sanborn                    |
      |Email Address   |BrandaJSanborn1@example.org|
    And click "Today"
    And fill form with:
      |Password        |BrandaJSanborn1@example.org|
      |Confirm Password|BrandaJSanborn1@example.org|
      |Customer        |Smoke Customer             |
    And fill "Customer User Addresses Form" with:
      |Primary                   |true         |
      |First Name Add            |Branda       |
      |Last Name Add             |Sanborn      |
      |Organization              |Smoke Org    |
      |Country                   |United States|
      |Street                    |Market St. 12|
      |City                      |San Francisco|
      |State                     |California   |
      |Zip/Postal Code           |90001        |
      |Billing                   |true         |
      |Shipping                  |true         |
      |Default Billing           |true         |
      |Default Shipping          |true         |
      |Administrator (Predefined)|true         |
    And save and close form
    Then should see "Customer User has been saved" flash message

  Scenario: Create customer from the frontstore
    Given I proceed as the User
    And I am on the homepage
    And click "Sign In"
    And click "Create An Account"
    And I fill "Registration Form" with:
      | Company Name     | OroCommerce              |
      | First Name       | Amanda                   |
      | Last Name        | Cole                     |
      | Email Address    | AmandaRCole1@example.org |
      | Password         | AmandaRCole1@example.org |
      | Confirm Password | AmandaRCole1@example.org |
    When I click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message

  Scenario: Create customer from the frontstore
    Given I proceed as the User
    When fill form with:
      |Email Address|AmandaRCole1@example.org|
      |Password     |AmandaRCole1@example.org|
    And click "Sign In"
    Then I should see "User account is locked"

  Scenario: Activate customer user
    Given  I proceed as the Admin
    When go to Customers/ Customer Users
    And click view "AmandaRCole1@example.org" in grid
    And click "Confirm"
    And go to Customers/ Customers
    And click edit "OroCommerce" in grid
    And fill form with:
      |Payment Term|net_10|
    And save and close form
    And I proceed as the User
    And fill form with:
      |Email Address|AmandaRCole1@example.org|
      |Password     |AmandaRCole1@example.org|
    And click "Sign In"
    Then should see "Signed in as: Amanda Cole"
    And click "Sign Out"

  Scenario: Create customer group
    Given I proceed as the Admin
    When go to Customers/ Customer Groups
    And click "Create Customer Group"
    And fill "Customer Group Form" with:
      |Name       |Smoke Group   |
      |Price List |FirstPriceList|
    And click on OroCommerce in grid
    And click on Smoke Customer in grid
    And save and close form
    Then should see "Customer group has been saved" flash message

  Scenario: Create payment and shipping integration
    Given I proceed as the Admin
    When go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And I fill "Integration Form" with:
      | Type | Flat Rate Shipping|
      | Name | Flat Rate         |
      | Label| Flat_Rate         |
    And save and close form
    Then I should see "Integration saved" flash message
    When go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And I fill "Integration Form" with:
      | Type       |Payment Terms|
      | Name       |Payment Terms|
      | Label      |Payment_Terms|
      | Short Label|Payment Terms|
    And save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create payment and shipping integration
    Given I proceed as the Admin
    When I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true       |
      | Name       | Flat Rate  |
      | Sort Order | 10         |
      | Currency   | $          |
      | Method     | Flat_Rate  |
    And fill form with:
      | Price | 10 |
    And save and close form
    Then should see "Shipping rule has been saved" flash message
    When I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "Payment Rule Form" with:
      | Enable     | true            |
      | Name       | Payment Terms   |
      | Sort Order | 1               |
      | Currency   | $               |
      | Method     | [Payment Terms] |
    When save and close form
    Then should see "Payment rule has been saved" flash message

  Scenario: Check Contact Us and About us pages, Products views, front filters, prices by not registered user
    Given I proceed as the User
    And I am on the homepage
    When click "About"
    Then Page title equals to "About"
    When click "Phones"
    Then should see "All Products / Phones"
    And should see "View Details" for "Lenovo_Vibe_sku" product
    And should see "Product Image" for "Lenovo_Vibe_sku" product
    And should see "Product Name" for "Lenovo_Vibe_sku" product
    And should see "Your Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Listed Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And click "Add to Shopping List" for "Lenovo_Vibe_sku" product
    And should see "Product has been added to "
    And should see "Green Box" for "Lenovo_Vibe_sku" product
    And should see "Update Shopping list" for "Lenovo_Vibe_sku" product
    And should see "View Details" for "Xiaomi_Redmi_3S_sku" product
    And should see "Product Image" for "Xiaomi_Redmi_3S_sku" product
    And should see "Product Name" for "Xiaomi_Redmi_3S_sku" product
    And should not see "Green Box" for "Xiaomi_Redmi_3S_sku" product
    And should see "Your Price: $150.00 / item" for "Xiaomi_Redmi_3S_sku" product
    And should see "Listed Price: $150.00 / item" for "Xiaomi_Redmi_3S_sku" product
    When click "Gallery View"
    Then should not see "View Details" for "Lenovo_Vibe_sku" product
    And should see "Product Image" for "Lenovo_Vibe_sku" product
    And should see "Product Name" for "Lenovo_Vibe_sku" product
    And should see "Your Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Listed Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Green Box" for "Lenovo_Vibe_sku" product
    And should see "Update Shopping list" for "Lenovo_Vibe_sku" product
    When click "No Image View"
    Then should see "View Details" for "Lenovo_Vibe_sku" product
    And should not see "Product Image" for "Lenovo_Vibe_sku" product
    And should see "Product Name" for "Lenovo_Vibe_sku" product
    And should see "Your Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Listed Price: $100.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Green Box" for "Lenovo_Vibe_sku" product
    And should see "Update Shopping list" for "Lenovo_Vibe_sku" product
    And click "List View"

  Scenario: Check Contact Us and About us pages, Products views, correct price for the product for customer user and for customer group
    Given I signed in as AmandaRCole1@example.org on the store frontend
    When click "About"
    Then Page title equals to "About"
    When click "Phones"
    Then should see "All Products / Phones"
    And should see "View Details" for "Lenovo_Vibe_sku" product
    And should see "Product Image" for "Lenovo_Vibe_sku" product
    And should see "Product Name" for "Lenovo_Vibe_sku" product
    And should see "Your Price: $80.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Listed Price: $80.00 / item" for "Lenovo_Vibe_sku" product
    And click "Add to Shopping List" for "Lenovo_Vibe_sku" product
    And should see "Product has been added to "
    And should see "Green Box" for "Lenovo_Vibe_sku" product
    And should see "Update Shopping List button" for "Lenovo_Vibe_sku" product
    And should see "View Details" for "Xiaomi_Redmi_3S_sku" product
    And should see "Product Image" for "Xiaomi_Redmi_3S_sku" product
    And should see "Product Name" for "Xiaomi_Redmi_3S_sku" product
    And should not see "Green Box" for "Xiaomi_Redmi_3S_sku" product
    And should see "Your Price: $120.00 / item" for "Xiaomi_Redmi_3S_sku" product
    And should see "Listed Price: $120.00 / item" for "Xiaomi_Redmi_3S_sku" product
    When click "Shirts"
    Then should see "Add to Shopping List button" for "Shirt_Sku" product
    And should see "View Details" for "Shirt_Sku" product
    And should see "Product Image" for "Shirt_Sku" product
    And should see "Product Name" for "Shirt_Sku" product
    And should not see "Your Price:" for "Shirt_Sku" product
    And should not see "Listed Price:" for "Shirt_Sku" product
    And click "View Details" for "Shirt_Sku" product
    And should not see "View Details"
    And should see an "Product Image (view page)" element
    And should see "ConfigurableShirt"
    And should see "Item"
    And should see an "Matrix Grid Form" element
    And should see an "Add to Shopping List" element
    And fill "Matrix Grid Form" with:
      |       | L | M |
      | Black | 2 | 3 |
      | White | 1 | 5 |
    And click "Add to Shopping List"
    And should see 'Shopping list "Shopping list" was updated successfully' flash message
    When I hover on "Shopping Cart"
    And click "View Details"
    And should see "Subtotal $175.20"
    And click "Sign Out"
    And I signed in as BrandaJSanborn1@example.org on the store frontend
    And click "Phones"
    When fill line item with "Lenovo_Vibe_sku" in frontend product grid:
      |Quantity|10  |
    Then should see "Your Price: $90.00 / item" for "Lenovo_Vibe_sku" product
    And should see "Listed Price: $80.00 / item" for "Lenovo_Vibe_sku" product
    When click "Add to Shopping List" for "Lenovo_Vibe_sku" product
    Then should see "Product has been added to "
    And I scroll to top
    When click "View Details" for "Xiaomi_Redmi_3S_sku" product
    Then should see "1 $120.00"
    And should see "10 $135.00"
    When I hover on "Shopping Cart"
    And click "View Details"
    Then should see "Subtotal $900.00"

  Scenario: Create shopping list, update shopping list, delete shopping list from front store
    Given I proceed as the User
    When click "Delete Shopping List"
    And click "Yes, Delete"
    Then should see "Shopping List deleted" flash message
    When I hover on "Shopping Cart"
    And click "Create New List"
    Then should see an "Create New Shopping List popup" element
    And type "New Front Shopping List" in "Shopping List Name"
    And click "Create"
    And should see "New Front Shopping List"
    And click "Phones"
    When fill line item with "Lenovo_Vibe_sku" in frontend product grid:
      |Quantity|112 |
    And click "Add to New Front Shopping List" for "Lenovo_Vibe_sku" product
    When I hover on "Shopping Cart"
    And click "New Front Shopping List"
    And I type "52" in "ShoppingListLineItemForm > Quantity"
    And I click on empty space
    And should see "Subtotal $4,680.00"
    Then I should see "Record has been successfully updated" flash message
    When I click on "Flash Message Close Button"
    And I click "Edit Shoppping List Label"
    And type "Updated Shopping List" in "Shopping List Label"
    And click "Save"
    Then should see "Record has been successfully updated" flash message

  Scenario: Checkout by customer created from admin througth the shopping list updated from admin panel
    Given I proceed as the Admin
    And go to Sales/ Shopping Lists
    When click on Updated Shopping List in grid
    And click "Add Line Item"
    Then should see an "Add Line Item Popup" element
    And fill form with:
      |Owner   |John Doe           |
      |Product |Xiaomi_Redmi_3S_sku|
      |Quantity|12                 |
      |Unit    |item               |
      |Notes   |test note Redmi    |
    And click "Save"
    Then should see "Line item has been added" flash message
    And should see "Total $6,300.00"
    When click edit "Lenovo Vibe" in grid
    And fill form with:
      |Quantity|64             |
      |Notes   |test note Vibe |
    And click "Save"
    Then should see "Line item has been updated" flash message
    And should see following grid:
      | SKU                 | Product         | Quantity | Unit | Notes           |
      | Lenovo_Vibe_sku     | Lenovo Vibe     | 64       | item | test note Vibe  |
      | Xiaomi_Redmi_3S_sku | Xiaomi Redmi 3S | 12       | item | test note Redmi |
    And should see "Total $7,380.00"
    When click delete "Lenovo Vibe" in grid
    And click "Yes, Delete"
    Then should see "Shopping List Line Item deleted" flash message
    And should see following grid:
      | SKU                 | Product         | Quantity | Unit | Notes           |
      | Xiaomi_Redmi_3S_sku | Xiaomi Redmi 3S | 12       | item | test note Redmi |
    And I should not see "Lenovo_Vibe_sku"
    And should see "Total $1,620.00"
    And I proceed as the User
    And reload the page

    When I hover on "Shopping Cart"
    And click "Updated Shopping List"

    And should see "test note Redmi"
    When click "Create Order"
    And fill form with:
      |SELECT BILLING ADDRESS|New address|
    And fill form with:
      |Label          |Home Address  |
      |First name     |NewFname      |
      |Last name      |NewLname      |
      |Organization   |NewOrg        |
      |Street         |Clayton St, 10|
      |City           |San Francisco |
      |Country        |United States |
      |State          |California    |
      |Zip/Postal Code|90001         |
    And click "Continue"
    And I select "Branda Sanborn, Smoke Org, Market St. 12, SAN FRANCISCO CA US 90001" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And should see "Subtotal $1,620.00"
    And should see "Shipping $120.00"
    And should see "Tax $153.90"
    And should see "TOTAL $1,893.90"
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And click "Sign Out"

  Scenario: Checkout by customer created from front store through the shopping list created by himself and review the submited order
    Given I signed in as AmandaRCole1@example.org on the store frontend
    And should see "1 Shopping List"
    When I open page with shopping list "Shopping List"
    And click "Create Order"
    And fill form with:
      |Label          |Home Address  |
      |First Name     |NewAmanda     |
      |Last Name      |NewCole       |
      |Organization   |NewOrg        |
      |Street         |Stanyan St 12 |
      |City           |San Francisco |
      |Country        |United States |
      |State          |California    |
      |Zip/Postal Code|90001         |
    And click "Ship to this address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And should see "Subtotal $175.20"
    And should see "Shipping $120.00"
    And should not see "Tax"
    And should see "TOTAL $295.20"
    When I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And click "click here to review"
    And should see "Billing Address Home Address NewAmanda NewCole NewOrg Stanyan St 12 SAN FRANCISCO CA US 90001"
    And should see "Shipping Address Home Address NewAmanda NewCole NewOrg Stanyan St 12 SAN FRANCISCO CA US 90001"
    And should see "Shipping Tracking Numbers N/A"
    And should see "Shipping Method Flat_Rate"
    And should see "Payment Method Payment Terms"
    And should see "Payment Term net_10"
    And should see "Payment Status Pending"
    And should see "Subtotal $175.20"
    And should see "Discount $0.00"
    And should see "Shipping $120.00"
    And should see "Tax $0.00"
    And should see "TOTAL $295.20"

  Scenario: Create RFQ, convert it to quote with edit and complete checkout by customer user
    Given I proceed as the User
    And click "Phones"
    And fill "FrontendLineItemForm" with:
      |Quantity|10  |
    And I scroll to top
    And click "Add to Shopping List"
    When I open page with shopping list "Shopping List"
    And click "Request Quote"
    And fill form with:
      |PO Number|PO00001|
    And click "Edit RFQ Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      |SKU         |Xiaomi_Redmi_3S_sku Xiaomi Redmi 3S|
      |Quantity    |20                 |
      |Target Price|110                |
    And click "Update Line Item"
    And should see "Xiaomi Redmi 3S QTY: 20 item Target Price $110.00 Listed Price: $120.00"
    And click "Delete Line Item"
    And should not see "Xiaomi Redmi 3S QTY: 20 item Target Price $110.00 Listed Price: $120.00"
    And click "Add Another Product"
    And fill "Frontstore RFQ Line Item Form2" with:
      |SKU         |Xiaomi_Redmi_3S_sku Xiaomi Redmi 3S|
      |Quantity    |30             |
      |Target Price|110            |
    And click "Update Line Item"
    And should see "Xiaomi Redmi 3S QTY: 30 item Target Price $110.00 Listed Price: $120.00"
    And click "Submit Request"
    And I proceed as the Admin
    And go to Sales/ Requests For Quote
    When click edit "PO00001" in grid
    And click "Add Another Product"
    And click "Add Another Line2"
    And fill "AdminPanel RFQ Line Item Form" with:
      |Quantity1    |12  |
      |Target Price1|112 |
      |SKU2         |Lenovo_Vibe_sku|
      |Quantity2    |30  |
      |Unit2        |item|
      |Target Price2|75  |
    And save and close form
    Then should see "Request has been saved" flash message
    When click "Create Quote"
    And save and close form
    And click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message
    And should see Quote with:
      | Quote #         | 1                |
      | PO Number       |PO00001           |
      | Internal Status | Sent to Customer |
      | Customer Status | N/A              |
    And I proceed as the User
    And click "Account"
    And click "Quotes"
    And click on PO00001 in grid
    When click "Accept and Submit to Order"
    And click "Submit"
    And fill form with:
      |Label          |Home Address  |
      |First name     |NewAmanda     |
      |Last name      |NewCole       |
      |Organization   |NewOrg        |
      |Street         |Stanyan St 12 |
      |City           |San Francisco |
      |Country        |United States |
      |State          |California    |
      |Zip/Postal Code|90001         |
    And click "Ship to this address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And should see "Subtotal $3,594.00"
    And should see "Shipping $420.00"
    And should not see "Tax"
    And should see "TOTAL $4,014.00"
    And click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And click "Sign Out"

  Scenario: Create Quote from admin and check that Customers able to checkout from it (Quote assigned to buyer, check out by admin)
    Given I proceed as the Admin
    And go to Customers/ Customer Users
    And click "Create Customer User"
    And fill form with:
      |First Name      |Lonnie                      |
      |Last Name       |Townsend                    |
      |Email Address   |LonnieVTownsend1@example.org|
    And click "Today"
    And fill form with:
      |Password        |LonnieVTownsend1@example.org|
      |Confirm Password|LonnieVTownsend1@example.org|
      |Customer        |OroCommerce                 |
    And fill "Customer User Addresses Form" with:
      |Primary                   |true           |
      |First Name Add            |Lonnie         |
      |Last Name Add             |Townsend       |
      |Organization              |OroCommerce    |
      |Country                   |United States  |
      |Street                    |Parnasus Ave 12|
      |City                      |San Francisco  |
      |State                     |California     |
      |Zip/Postal Code           |90001          |
      |Billing                   |true           |
      |Shipping                  |true           |
      |Default Billing           |true           |
      |Default Shipping          |true           |
      |Administrator (Predefined)|true           |
    And save and close form
    When go to Sales/ Quotes
    And click "Create Quote"
    When fill "Quote Form" with:
      |Customer        |OroCommerce    |
      |Customer User   |Amanda Cole    |
      |PO Number       |PO1001         |
      |LineItemProduct |Lenovo_Vibe_sku|
      |LineItemPrice   |82             |
      |LineItemQuantity|35             |
    And save and close form
    And click "Save on conf window"
    And click "Send to Customer"
    And click "Send"
    Then I should see "Quote #2 successfully sent to customer" flash message
    And I proceed as the User
    And I signed in as LonnieVTownsend1@example.org on the store frontend
    And click "Account"
    And click "Quotes"
    When click on PO1001 in grid
    When click "Accept and Submit to Order"
    And click "Submit"
    And I select "Lonnie Townsend, OroCommerce, Parnasus Ave 12, SAN FRANCISCO CA US 90001" on the "Billing Information" checkout step and press Continue
    And I select "Lonnie Townsend, OroCommerce, Parnasus Ave 12, SAN FRANCISCO CA US 90001" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Customer User with Administrator privileges have ability to see orders, RFQ, quotes of other users for the same customer
    Given I proceed as the User
    And click "Account"
    When click "Requests For Quote"
    And click on PO00001 in grid
    Then should see "First Name Amanda"
    And should see "Last Name Cole"
    And should see "Email Address AmandaRCole1@example.org"
    And should see "PO Number PO00001"
    And should see "Owner Amanda Cole"
    And should see "Xiaomi Redmi 3S Item #: Xiaomi_Redmi_3S_sku 12 items $112.00"
    And should see "Lenovo Vibe Item #: Lenovo_Vibe_sku 30 items $75.00"
    When click "Quotes"
    Then should see following grid:
      | Quote # | PO Number | Owner       |
      | 1       | PO00001   | Amanda Cole |
      | 2       | PO1001    | Amanda Cole |
    When click "Order History"
    And click on $295.20 in grid
    Then should see "Billing Address Home Address NewAmanda NewCole NewOrg Stanyan St 12 SAN FRANCISCO CA US 90001"
    And should see "Shipping Address Home Address NewAmanda NewCole NewOrg Stanyan St 12 SAN FRANCISCO CA US 90001"
    And should see "Shipping Tracking Numbers N/A"
    And should see "Shipping Method Flat_Rate"
    And should see "Payment Method Payment Terms"
    And should see "Payment Term net_10"
    And should see "Payment Status Pending"
    And should see "Subtotal $175.20"
    And should see "Discount $0.00"
    And should see "Shipping $120.00"
    And should see "Tax $0.00"
    And should see "TOTAL $295.20"

  Scenario: Customer User with Administrator privileges create/update/block/delete new Customer User
    Given I proceed as the User
    And click "Account"
    And click "Roles"
    When click edit "Buyer" in grid
    And fill form with:
      |Role Title|NewByerRole|
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message
    When click "Users"
    And click "Create User"
    And fill form with:
      |Email Address             |TestUser1@test.com|
      |First Name                |TestF             |
      |Last Name                 |TestL             |
      |Password                  |TestUser1@test.com|
      |Confirm Password          |TestUser1@test.com|
      |NewByerRole (Customizable)|true              |
    And click "Save"
    Then should see "Customer User has been saved" flash message
    And click "Sign Out"
    When I signed in as TestUser1@test.com on the store frontend
    Then should see "Signed in as: TestF TestL"
    And click "Sign Out"
    And I signed in as LonnieVTownsend1@example.org on the store frontend
    When click "Account"
    And click "Users"
    And click disable "TestUser1@test.com" in grid
    And click "Sign Out"
    And I signed in as TestUser1@test.com on the store frontend
    Then should see "User account is locked"
    And I signed in as LonnieVTownsend1@example.org on the store frontend
    When click "Account"
    And click "Users"
    And click edit "TestUser1@test.com" in grid
    And fill form with:
      |Enable     |true       |
      |Name Prefix|Test Prefix|
    And click "Save"
    Then should see "Customer User has been saved" flash message
    When click "Users"
    And click delete "TestUser1@test.com" in grid
    And click "Yes, Delete"
    Then should see "Customer User deleted" flash message
    And click "Users"
    And should not see "TestUser1@test.com"
