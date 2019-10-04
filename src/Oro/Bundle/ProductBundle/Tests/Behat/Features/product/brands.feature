@fixture-OroProductBundle:highlighting_new_products.yml
@fixture-OroOrganizationProBundle:GlobalOrganizationFixture.yml
Feature: Brands
  In order to add sort products by brands
  As an Administrator
  I want to be able to create and assign brands to the product

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable the existing localizations

  Scenario: Create Brands
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/ Product Brands
    And click "Create Brand"
    And fill "Brand Form" with:
      | Name             | ACME             |
      | Status           | Enable           |
      | Meta Title       | apple            |
      | Meta Description | Meta Description |
      | Meta Keywords    | MetaKeyword      |
    And I click on "Brand Form Name Fallbacks"
    And fill "Brand Form" with:
      | Name First Use Default  | false                 |
      | Name First              | ACME (Default locale) |
      | Name Second Use Default | false                 |
      | Name Second             | ACME (Zulu locale)    |
    And save and close form
    Then I should see "Brand has been saved" flash message
    And go to Products/ Product Brands
    And click "Create Brand"
    And fill "Brand Form" with:
      | Name             | Spectrum         |
      | Status           | Enable           |
      | Meta Title       | banana           |
      | Meta Description | Meta Description |
      | Meta Keywords    | Pineapple        |
    And I click on "Brand Form Name Fallbacks"
    And fill "Brand Form" with:
      | Name First Use Default  | false                     |
      | Name First              | Spectrum (Default locale) |
      | Name Second Use Default | false                     |
      | Name Second             | Spectrum (Zulu locale)    |
    And save and close form
    Then I should see "Brand has been saved" flash message

  Scenario: Assign Brand to the product
    Given I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When click "Brand humburger button"
    And filter Brand as Contains "ACME"
    Then I should see following "Brand Select Grid" grid:
      | Brand |
      | ACME  |
    And should not see "Spectrum"
    And click on ACME in grid "Brand Select Grid"
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Search by Brand and default locale
    And I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "NewCategory"
    When type "ACME (Default locale)" in "search"
    And click "Search Button"
    Then I should see "PSKU1"
    And I should not see "PSKU2"

  Scenario: Check Brand visibility for the product with changed language
    Given I proceed as the User
    When click "View Details" for "PSKU1" product
    Then I should see "ACME (Default locale)"
    Given I click on "Localization dropdown"
    And I click "Zulu"
    Then I should see "ACME (Zulu locale)"

  Scenario: Search by Brand and zulu locale
    And I proceed as the User
    And click "NewCategory"
    When type "ACME (Zulu locale)" in "search"
    And click "Search Button"
    Then I should see "PSKU1"
    And I should not see "PSKU2"

  Scenario: Check Brand visibility for the product view page
    Given I proceed as the User
    And I click "NewCategory"
    When click "View Details" for "PSKU1" product
    Then I should see "ACME (Zulu locale)"
    And should not see "Spectrum (Zulu locale)"
    And I click "NewCategory"
    When click "View Details" for "PSKU2" product
    Then I should not see "ACME (Zulu locale)"
    And should not see "Spectrum (Zulu locale)"
    And I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When fill form with:
      | Brand | Spectrum |
    And save and close form
    And I should see "Product has been saved" flash message
    And I proceed as the User
    And I click "NewCategory"
    And click "View Details" for "PSKU1" product
    Then I should not see "ACME (Zulu locale)"
    And should see "Spectrum (Zulu locale)"
    And I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When click "Clear Brand"
    And save and close form
    And I should see "Product has been saved" flash message
    And I proceed as the User
    And reload the page
    Then I should not see "ACME (Zulu locale)"
    And should not see "Spectrum (Zulu locale)"

  Scenario: Add to custom product family
    Given I proceed as the Admin
    And I go to Products/ Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label          | Visible | Attributes                                                            |
      | General        | true    | [SKU, Name, Is Featured, New Arrival, Description, Short Description] |
      | Inventory      | true    | [Inventory Status]                                                    |
      | Images         | true    | [Images]                                                              |
      | Product Prices | true    | [Product prices]                                                      |
      | SEO            | true    | [Meta title, Meta description, Meta keywords]                         |
      | Custom         | true    | [Brand]                                                               |
    When I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Assign Brand to the third product
    Given I go to Products/ Products
    And I click edit "PSKU3" in grid
    When fill form with:
      | Brand | ACME |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check Brand label is visible
    Given I proceed as the User
    And I signed in as NancyJSallee@example.org on the store frontend
    When I click "NewCategory"
    And I click "View Details" for "PSKU3" product
    Then I should see "Brand: ACME (Default locale)"

  Scenario: Check Brand visibility for the third product with changed language
    When I click on "Localization dropdown"
    And I click "Zulu"
    Then I should see "Brand: ACME (Zulu locale)"

  Scenario: Hide brand label
    Given I proceed as the Admin
    And I am logged in under Globe ORO Pro organization
    And go to Products/ Product Attributes
    And I click on Brand in grid
    When fill form with:
      | Label | _Brand |
    And I save and close form
    Then I should see "Translation cache update is required. Click here to update" flash message
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Check Brand label is not visible
    Given I proceed as the User
    When I click "NewCategory"
    And I click "View Details" for "PSKU3" product
    Then I should not see "Brand: ACME (Zulu locale)"
    Then I should see "ACME (Zulu locale)"
