@feature-BB-20992
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Human-readable uploaded filenames
  In order to see uploaded files and images
  As a Buyer
  I should see an uploaded files and images with appended normalized original filenames to the hash values in the filenames

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check configuration settings
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    Then I should see "Enable Original File Names"
    And I should see "Modification of the default value may cause temporary storefront slow-down until all images are renamed. The URLs in the search index will not be updated immediately and will require manual start of the search re-index. Make sure that the harddrive has at least 50% space available as the renamed images will be stored alongside the existing ones."
    When I follow "Commerce/Product/Product Images" on configuration sidebar
    Then I should not see "Enable Original File Names"

  Scenario: Create product attributes
    When I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | ImageField |
      | Type       | Image      |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)     | 10                      |
      | Thumbnail Width    | 50                      |
      | Thumbnail Height   | 50                      |
      | Allowed MIME types | [image/png, image/jpeg] |
      | File applications  | [default, commerce]     |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | FileField |
      | Type       | File      |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)     | 10                                       |
      | Allowed MIME types | [application/pdf, image/png, image/jpeg] |
      | File applications  | [default, commerce]                      |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [ImageField, FileField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | ImageField | cat1.jpg |
      | FileField  | cat2.jpg |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check attributes with human-readable filenames are available at storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    Then I should see "ImageField"
    And I should see an "ImageField Image" element
    And "ImageField Image" element "src" attribute should contain "-cat1.jpg"
    And I should see "cat2.jpg" link with the url matches "/attachment/.+?-cat2\.jpg"

  Scenario: Disable "Original File Names" configuration option
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    And uncheck "Use default" for "Enable Original File Names" field
    And uncheck "Enable Original File Names"
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Product/Product Images" on configuration sidebar
    Then I should see "Enable Original File Names"
    And I should see "Modification of the default value may cause temporary storefront slow-down until all images are renamed. The URLs in the search index will not be updated immediately and will require manual start of the search re-index. Make sure that the harddrive has at least 50% space available as the renamed images will be stored alongside the existing ones."
    And I should see "Use default" for "Enable Original File Names" field
    And the "Enable Original File Names" checkbox should be unchecked

  Scenario: Check attributes without human-readable filenames are available at storefront
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "ImageField"
    And I should see an "ImageField Image" element
    And "ImageField Image" element "src" attribute should not contain "-cat1.jpg"
    And I should not see "cat2.jpg" link with the url matches "/attachment/.+?-cat2\.jpg"
