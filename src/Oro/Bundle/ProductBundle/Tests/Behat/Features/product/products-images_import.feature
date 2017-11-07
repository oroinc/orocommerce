@ticket-BB-9215

Feature: Product Images Import
  Would be good to add possibility to import product images via CSV file - e.g. there might be full paths to required images.
  EDIT: Images will be uploaded though ftp or other method. Import process will parse the csv file with the image paths and link the images to the corresponding product.
  EDIT: Check if it is possible to use same image for multiple products
  EDIT 28.08.2017 :
  As an Administrator
  I want to be able to import product images by CSV file with the following structure:
  |SKU	|Name	    |Main	|Listing|Additional|
  |123AA|123AA_1.jpg|1	    |0      |1         |
  1. On the server there will be a predefined folder where the images are previously uploaded.
  2. The Administrator goes to Products list page where he will have a new option in the "Import' options : "Import product images from server'
  3. Upload the csv file with the previous structure

  Scenario: Data Template for Products
    Given I login as administrator
    And I go to Products/ Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    Then I see attributeFamily.code column
    And I see sku column
    And I see status column
    And I see type column
    And I see inventory_status.id column
    And I see primaryUnitPrecision.unit.code column
    And I see primaryUnitPrecision.precision column
    And I see names.default.value column
    And I see featured column
    And I see newArrival column

  Scenario: Import new Products
    Given I fill template with data:
      |attributeFamily.code|sku   |status  |type   |inventory_status.id |primaryUnitPrecision.unit.code|primaryUnitPrecision.precision|names.default.value|featured | newArrival|
      |default_family      |SKU1  |enabled |simple |in_stock            |set                            |3                             |Product1           |true     |true       |
      |default_family      |SKU2  |enabled |simple |in_stock            |item                           |1                             |Product2           |true     |true       |
    When I import file
    #And Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I reload the page
    And I should see following grid:
      |SKU   |NAME     |Inventory status    |Status    |
      |SKU2  |Product2 |In Stock            |Enabled   |
      |SKU1  |Product1 |In Stock            |Enabled   |
    And number of records should be 2
    And I click view "SKU1" in grid
    And I should see Product with:
      |SKU          |SKU1     |
      |Name         |Product1 |
      |Type         |simple   |
      |Is Featured  |Yes      |
      |New Arrival  |Yes      |
    And go to Products/Products
    And I click view "SKU2" in grid
    And I should see Product with:
      |SKU          |SKU2    |
      |Name         |Product2|
      |Type         |simple  |
      |Is Featured  |Yes     |
      |New Arrival  |Yes     |

  Scenario: Data Template for Product Images
    Given I login as administrator
    And I go to Products/ Products
    When I download "Product images" Data Template file through "Import images" button
    Then I see SKU column
    And I see Name column
    And I see Main column
    And I see Listing column
    And I see Additional column

  Scenario: Import new Product Images
    Given I upload product images files
    And I fill template with data:
      |SKU |Name              |Main  |Listing   |Additional|
      |SKU1|dog1.jpg        |1     |1         |1         |
      |SKU2|dog1.jpg        |0     |0         |1         |
    When I import file with "Import images" button
    #And Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I reload the page
    And I click view "SKU1" in grid
    And I should see "dog1.jpg"
    And I go to Products/Products
    And I click view "SKU2" in grid
    And I should see "dog1.jpg"
