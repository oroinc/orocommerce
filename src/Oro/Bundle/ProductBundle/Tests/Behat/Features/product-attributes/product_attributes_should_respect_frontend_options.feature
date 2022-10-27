@regression
@ticket-BB-19911
@ticket-BB-19652
@ticket-BB-20956
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attributes should respect Frontend options
  In order to have custom attributes for Product entity
  As an Administrator
  I should be able to define whether the field is visible or hidden on the storefront

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario Outline: Create product attributes with type Image/Multiple Images
    When I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | <Name> |
      | Type       | <Type> |
    And I click "Continue"
    And I fill form with:
      | Show on view      | Yes                 |
      | File Size (MB)    | 10                  |
      | Thumbnail Width   | 1900                |
      | Thumbnail Height  | 1200                |
      | File applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    Examples:
      | Name           | Type            |
      | ImageField     | Image           |
      | MultipleImages | Multiple Images |

  Scenario Outline: Create product attributes with type File/Multiple Files
    When I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | <Name> |
      | Type       | <Type> |
    And I click "Continue"
    And I fill form with:
      | Show on view      | Yes                 |
      | File Size (MB)    | 10                  |
      | File applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    Examples:
      | Name          | Type           |
      | FileField     | File           |
      | MultipleFiles | Multiple Files |

  Scenario: Create product attributes with type WYSIWYG
    When I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | WYSIWYGField |
      | Type       | WYSIWYG      |
    And I click "Continue"
    And I fill form with:
      | Show on view      | Yes                 |
      | File applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [ImageField, MultipleImages, FileField, MultipleFiles, WYSIWYGField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | ImageField       | cat1.jpg     |
      | FileField        | example2.pdf |
      | MultipleImages 1 | cat2.jpg     |
      | MultipleFiles 1  | example2.pdf |
    And I fill in WYSIWYG "Product WYSIWYGField Attribute Content" with "WYSIWYG Field content"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check attributes on the storefront
    Given I proceed as the Buyer
    When I login as AmandaRCole@example.org buyer
    And I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    Then I should see "ImageField"
    And I should see "FileField"
    And I should see "MultipleImages"
    And I should see "MultipleFiles"
    And I should see "WYSIWYG Field content"

  Scenario Outline: Hide attributes on the storefront through "Show on view" option
    Given I proceed as the Admin
    When I go to Products/ Product Attributes
    And I click Edit <AttributeName> in grid
    And I fill form with:
      | Show on view | No |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    Examples:
      | AttributeName  |
      | ImageField     |
      | FileField      |
      | MultipleImages |
      | MultipleFiles  |
      | WYSIWYGField   |

  Scenario: Check that attributes is not displayed on storefront
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "ImageField"
    And I should not see "FileField"
    And I should not see "MultipleImages"
    And I should not see "MultipleFiles"
    And I should not see "WYSIWYG Field content"
    And I proceed as the Admin

  Scenario Outline: Hide attributes on the storefront through "File applications" option
    When I go to Products/ Product Attributes
    And I click Edit <AttributeName> in grid
    And I fill form with:
      | Show on view      | Yes       |
      | File applications | [default] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    Examples:
      | AttributeName  |
      | ImageField     |
      | FileField      |
      | MultipleImages |
      | MultipleFiles  |
      | WYSIWYGField   |

  Scenario: Check that attributes is not displayed on storefront
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "ImageField"
    And I should not see "FileField"
    And I should not see "MultipleImages"
    And I should not see "MultipleFiles"
    And I should not see "WYSIWYG Field content"

  Scenario: Show image type attributes on the storefront for following validation of visibility
    Given I proceed as the Admin
    When I go to Products/ Product Attributes
    And I click Edit ImageField in grid
    And I fill form with:
      | Show on view      | Yes       |
      | File applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: The visibility to a logged in customer user should not depend on the storefront guest access settings
    When I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    When I save form
    Then I should see "Configuration Saved" flash message
    When I proceed as the Buyer
    And I reload the page
    Then I should see an "Uploaded Product Image" element
    And "Uploaded Product Image" element "src" attribute should not contain "admin"
