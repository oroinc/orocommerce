@regression
@ticket-BB-9215
@ticket-BB-17506

Feature: Product Images Import

#  Would be good to add possibility to import product images via CSV file - e.g. there might be full paths to required images.
#  EDIT: Images will be uploaded though ftp or other method. Import process will parse the csv file with the image paths and link the images to the corresponding product.
#  EDIT: Check if it is possible to use same image for multiple products
#  EDIT 28.08.2017 :
#  As an Administrator
#  I want to be able to import product images by CSV file with the following structure:
#  |SKU	|Name	    |Main	|Listing|Additional|
#  |123AA|123AA_1.jpg|1	    |0      |1         |
#  1. On the server there will be a predefined folder where the images are previously uploaded.
#  2. The Administrator goes to Products list page where he will have a new option in the "Import' options : "Import product images from server'
#  3. Upload the csv file with the previous structure

  Scenario: Create different window session
    Given sessions active:
      | Admin|first_session |
      | User |second_session|

  Scenario: Data Template for Products
    Given I proceed as the Admin
    And login as administrator
    And go to Products/ Products
    And I open "Products" import tab
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    Then see attributeFamily.code column
    And see sku column
    And see status column
    And see type column
    And see inventory_status.id column
    And see primaryUnitPrecision.unit.code column
    And see primaryUnitPrecision.precision column
    And see names.default.value column
    And see featured column
    And see newArrival column

  Scenario: Import new Products
    Given I proceed as the Admin
    And fill template with data:
      |attributeFamily.code|sku   |status  |type   |inventory_status.id |primaryUnitPrecision.unit.code|primaryUnitPrecision.precision|names.default.value|featured | newArrival|
      |default_family      |SKU1  |enabled |simple |in_stock            |set                            |3                             |Product1           |true     |true       |
      |default_family      |SKU2  |enabled |simple |in_stock            |item                           |1                             |Product2           |true     |true       |
    When import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And reload the page
    And should see following grid:
      |SKU   |NAME     |Inventory status|Status    |
      |SKU2  |Product2 |In Stock        |Enabled   |
      |SKU1  |Product1 |In Stock        |Enabled   |
    And number of records should be 2
    And click view "SKU1" in grid
    And should see Product with:
      |SKU          |SKU1     |
      |Name         |Product1 |
      |Type         |simple   |
      |Is Featured  |Yes      |
      |New Arrival  |Yes      |
    And go to Products/Products
    When click view "SKU2" in grid
    Then should see Product with:
      |SKU          |SKU2    |
      |Name         |Product2|
      |Type         |simple  |
      |Is Featured  |Yes     |
      |New Arrival  |Yes     |

  Scenario: Data Template for Product Images
    Given I proceed as the Admin
    And go to Products/ Products
    And I open "Product Images" import tab
    When I download "Product Images" Data Template file
    Then see SKU column
    And see Name column
    And see Main column
    And see Listing column
    And see Additional column

  Scenario: Import new Product Images
    Given I upload product images files
    And fill template with data:
      |SKU |Name    |Main  |Listing   |Additional|
      |SKU1|dog1.jpg|1     |1         |1         |
      |SKU2|dog1.jpg|0     |0         |1         |
    And I open "Product Images" import tab
    When import file
    And Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And reload the page
    And click view "SKU1" in grid
    And should see "dog1.jpg"
    And go to Products/Products
    When click view "SKU2" in grid
    Then should see "dog1.jpg"

  Scenario: Import new Product Images with one more main image
    Given I proceed as the Admin
    And go to Products/ Products
    And I open "Product Images" import tab
    When fill template with data:
      | SKU  | Name        | Main | Listing | Additional |
      | SKU1 |             | 1    | 0       | 1          |
      | SKU1 | missing.jpg | 1    | 0       | 1          |
      | SKU1 | dog1.jpg    | 1    | 0       | 1          |
    And I import file
    Then Email should contains the following "Errors: 4 processed: 1, read: 3, added: 1, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Failed to either upload or clone file from the existing one: file not found by specified UUID and nothing is specified for uploading. Please make sure image.URI and image.UUID columns are present in the import file and have the correct values."
    And I should see "Error in row #1. File importing has failed, entity is skipped"
    And I should see "Error in row #2. Failed to upload a file from"
    And I should see "Error in row #2. File importing has failed, entity is skipped"
    When go to "/admin/product"
    And I open "Product Images" import tab
    And fill template with data:
      | SKU  | Name       | Main | Listing | Additional |
      | SKU1 | gecko1.jpg | 1    | 0       | 1          |
    And I import file
    Then Email should not contains the following:
      | Body | Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0 |
    When click view "SKU1" in grid
    Then I should see following product images:
      | gecko1.jpg | 1 |   | 1 |
      | dog1.jpg   |   | 1 | 1 |
      | dog1.jpg   |   |   | 1 |
    When I click "Edit"
    Then should see "dog1.jpg"
    And should see "gecko1.jpg"

  Scenario: Check if there Product Images on frontend
    Given I proceed as the User
    And I am on the homepage
    When type "SKU" in "search"
    And click "Search Button"
    Then should see "Uploaded Product Image" for "SKU1" product
    And should see "Empty Product Image" for "SKU2" product
    When click "View Details" for "SKU2" product
    Then I should see an "Uploaded Product Image" element
    And should not see an "Empty Product Image" element

  Scenario: Check a work validation after clicking "Choose File"
    Given I proceed as the Admin
    And I go to Products/Products
    And I click "Import file"
    And I open "Product Images" import tab
    When I click "Validate"
    Then I should see "This value should not be blank."
    And I click "Cancel"
    And I click "Import file"
    And I open "Product Images" import tab
    When I attach "product_images_import/dog1.jpg" for Product Images
    Then I should not see "This value should not be blank."
