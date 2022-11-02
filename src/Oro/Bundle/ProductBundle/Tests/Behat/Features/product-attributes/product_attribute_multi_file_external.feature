@regression
@ticket-BAP-20940
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attribute multi file external
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add Multiple Files product attribute with Stored Externally

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I copy product fixture "000.png" to public directory as "image.png"
    And I copy product fixture "000.jpg" to public directory as "image.jpg"

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | MultiFileField |
      | Type       | Multiple Files |
    And I click "Continue"
    And I fill form with:
      | Stored Externally | Yes |
    Then I should not see "File Size (MB)"
    And I should not see "File applications"
    When I fill form with:
      | Allowed MIME types | [image/png] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [MultiFileField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check the error when no external files and images are allowed
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | MultiFileField 1 | http://example.org/image.png |
    And I save and close form
    Then I should see "Product Form" validation errors:
      | MultiFileField 1 | No external files and images are allowed. Allowed URLs RegExp can be configured on the following page: System -> Configuration -> General Setup -> Upload Settings. |

  Scenario: Check the error when the URL does not match allowed
    Given I set configuration property "oro_attachment.external_file_allowed_urls_regexp" to "^http://localhost/"
    When I fill "Product Form" with:
      | MultiFileField 1 | http://example.org/image.png |
    And I save and close form
    Then I should see "Product Form" validation errors:
      | MultiFileField 1 | The provided URL does not match the URLs allowed in the system configuration. |

  Scenario: Check the error when invalid MIME type
    Given I set configuration property "oro_attachment.external_file_allowed_urls_regexp" to ".+?"
    When I fill "MultiFileField 1" with absolute URL "/media/cache/fixtures/image.jpg" in form "Product Form"
    And I save and close form
    Then I should see "Product Form" validation errors:
      | MultiFileField 1 | The MIME type of the file is invalid ("image/jpeg"). Allowed MIME types are "image/png". |

  Scenario: Specify the correct external file URL to MultiFileField
    When I fill "MultiFileField 1" with absolute URL "/media/cache/fixtures/image.png" in form "Product Form"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check file attribute is available at store front
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    Then I should see "MultiFileField"
    And I should see "image.png" link with the url matches "/media/cache/fixtures/image.png"

  Scenario: Delete product attribute
    Given I proceed as the Admin
    And I go to Products/ Product Attributes
    When I click Remove "MultiFileField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    When I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
