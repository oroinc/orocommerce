# GridFS cannot give the MIME type of the JSON template, and the download returns HTTP 500, see BB-28055
@gridfs-skip

Feature: External orders import data template
  In order to prepare orders from the 3-party systems for import
  As an Administrator
  I want to be able to download the JSON data template for orders

  Scenario: Enable External orders import
    Given I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Orders/External Order Import" on configuration sidebar
    When I uncheck "Use default" for "Enable JSON Order Import" field
    And I check "Enable JSON Order Import"
    And I save form
    Then I should see "Configuration saved" flash message
    And the "Enable JSON Order Import" checkbox should be checked

  Scenario: Data Template for Orders
    When I go to Sales/ Orders
    Then there is no records in grid
    When I download Data Template file with processor "external_order_import" and job "entity_export_template_to_json"
    Then I see "identifier" field in JSON template
    And I see "poNumber" field in JSON template
    And I see "lineItems" field in JSON template
    And I see "billingAddress" field in JSON template
    And I see "shippingAddress" field in JSON template
