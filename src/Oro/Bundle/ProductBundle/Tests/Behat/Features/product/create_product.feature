@regression
@ticket-BB-9207
@ticket-BB-17327
@ticket-BB-17630
@ticket-BB-19940
@ticket-BB-20744
@ticket-BB-10466
@waf-skip
@automatically-ticket-tagged
@fixture-OroCatalogBundle:categories.yml
@fixture-OroCustomerBundle:CustomerUserFixture.yml
Feature: Create product
  In order to manage products
  As administrator
  I need to be able to create product with images
  As a buyer I need to be able to see created product on store front

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: "Product 1A" > CHECK ABILITY TO GO TO THE SECOND STEP PRODUCT CREATION FORM DURING SUBMIT BY PRESSING ENTER KEY.
    Given I proceed as the Admin
    And I login as administrator
    And go to Products/ Products
    Then Page title equals to "Products - Products"
    And I should see "Products / Products" in breadcrumbs
    When click "Create Product"
    Then Page title equals to "Create Product - Products - Products"
    And I should see "Products / Products" in breadcrumbs
    When I focus on "Type" field and press Enter key
    Then I should see "Save and Close"

  Scenario: Check second step of product creation form
    Given I go to Products/ Products
    And I click "Create Product"
    And I click "Retail Supplies"
    When I click "Continue"
    Then Page title equals to "Create Product - Products - Products"
    And I should see "Products / Products" in breadcrumbs
    And I should see "Type: Simple Product Family: Default Category: All Products / Retail Supplies"

  Scenario: Finalizing product creation
    Given fill "Create Product Form" with:
      | SKU              | Test123                                    |
      | Name             | Test Product                               |
      | Status           | Enable                                     |
      | Unit Of Quantity | item                                       |
      | Description      | <iframe src='http://example.org'></iframe> |
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     |         | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I set Images with:
      | Main  | Listing | Additional |
      |       | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid
    When I save form
    Then I should see "Please remove not permitted HTML-tags in the content field: - \"src\" attribute on \"<iframe>\" should be removed (near <iframe src=" error message
    When fill "Create Product Form" with:
      | Description | Sample content <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><a title=\"cat1_wysiwyg_file\" href=\"{{ wysiwyg_file(13, '902dfb57-57c0-4a2f-88bf-adf365d74895') }}\">File of cat1</a> |
    And I save form
    Then I should see "Product has been saved" flash message
    And I should not see text matching "\{\{ wysiwyg_image\(" in WYSIWYG editor
    And I should not see text matching "\{\{ wysiwyg_file\(" in WYSIWYG editor
    And I remember "listing" image filtered ID
    And I remember "main" image filtered ID

  Scenario: Check created product on grid
    Given I go to Products/ Products
    When I filter SKU as is equal to "Test123"
    Then I should see remembered "listing" image for product with "Test123"
    And I should not see remembered "main" image for product with "Test123"

    When I click on Image cell in grid row contains "Test123"
    Then I should see remembered "main" image preview
    And I close large image preview

  Scenario: Check created product on view page
    When I click view "Test123" in grid
    Then Page title equals to "Test123 - Test Product - Products - Products"
    And I should see "Products / Products" in breadcrumbs
    And I should see product with:
      | SKU            | Test123      |
      | Name           | Test Product |
      | Type           | Simple       |
      | Product Family | Default      |
    And image "cat1 wysiwyg image" is loaded
    And I remember filename of the image "cat1 wysiwyg image"
    And I remember filename of the file "cat1 wysiwyg file"
    And I click on "More Link"
    And I should see "File of cat1"

  Scenario: Check image is displayed on store front
    Given I proceed as the Buyer
    And I am on the homepage
    And I type "test123" in "search"
    And I click "Search Button"
    When I click "View Details" for "test123" product
    Then image "cat1 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I should see "File of cat1"

  Scenario: Add another image to description
    Given I proceed as the Admin
    And I click "Edit"
    When fill "Product Form" with:
      | Description | Sample content <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><a title=\"cat1_wysiwyg_file\" href=\"{{ wysiwyg_file(13, '902dfb57-57c0-4a2f-88bf-adf365d74895') }}\">File of cat1</a> Another image: <img alt=\"cat2_wysiwyg_image\" src=\"{{ wysiwyg_image(14, 'c840eec3-4b10-4682-b5cd-4d51fe008b6f') }}\"/> |
    And I save form
    Then I should not see text matching "\{\{ wysiwyg_image\(" in WYSIWYG editor
    And I should not see text matching "\{\{ wysiwyg_file\(" in WYSIWYG editor
    When I save and close form
    Then I should see "Product has been saved" flash message
    And image "cat2 wysiwyg image" is loaded
    And I remember filename of the image "cat2 wysiwyg image"
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I should see "File of cat1"

  Scenario: Check images are displayed on store front
    Given I proceed as the Buyer
    When I reload the page
    Then image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the image "cat2 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I should see "File of cat1"

  Scenario: Disable guest access and check product image is still visible on grid and form
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    When uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    And I save form
    Then I should see "Configuration Saved" flash message

    When I go to Products/ Products
    And I filter SKU as is equal to "Test123"
    Then I should see remembered "listing" image for product with "Test123"
    And I should not see remembered "main" image for product with "Test123"

    When I click on Image cell in grid row contains "Test123"
    Then I should see remembered "main" image preview
    And I close large image preview

    When I click edit "Test123" in grid
    Then I should see remembered "main" image in "Product Form Images Section" element
    And I should see remembered "listing" image in "Product Form Images Section" element

  Scenario: Change digital asset image
    Given I go to Marketing/ Digital Assets
    And I click edit "cat1.jpg" in grid
    And I fill "Digital Asset Form" with:
      | File | blue-dot.jpg |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message

  Scenario: Check new image is displayed on store front
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I type "test123" in "search"
    And I click "Search Button"
    When I click "View Details" for "test123" product
    Then image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is not as remembered
    And filename of the image "cat2 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is not as remembered
    And I should see "File of cat1"

  Scenario: Prevent links in product descriptions content
    Given I proceed as the Admin
    And I go to Products/Products
    And click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU               | Test1234                                                           |
      | Name              | Test Product 1                                                     |
      | Status            | Enable                                                             |
      | Unit Of Quantity  | item                                                               |
      | Short Description | <a href=\"#\">link 1 in short</a><a href=\"/\">link 2 in short</a> |
      | Description       | <a href=\"#\">link 1 in desc</a><a href=\"/\">link 2 in desc</a>   |
  And I save and close form
  And I should see product with:
      | SKU | Test1234 |
  When I click "link 1 in short"
  Then I should see "This click cannot be processed in the preview mode." flash message and I close it
  And I should see product with:
    | SKU | Test1234 |
  When I click "link 2 in short"
  Then I should see "This click cannot be processed in the preview mode." flash message and I close it
  And I should see product with:
    | SKU | Test1234 |
  When I click "link 1 in desc"
  Then I should see "This click cannot be processed in the preview mode." flash message and I close it
  And I should see product with:
    | SKU | Test1234 |
  When I click "link 2 in desc"
  Then I should see "This click cannot be processed in the preview mode." flash message and I close it
  And I should see product with:
    | SKU | Test1234 |
