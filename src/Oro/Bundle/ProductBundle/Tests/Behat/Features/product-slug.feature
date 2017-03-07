Feature: Slug generations based on product name
  In order to provide users with human readable product urls
  As administrator
  I need to be able to change product name and get generated slug prototypes independently of Slug permissions

  Scenario: "Product slug 1A" > CREATE PRODUCT WITH SLUG VIEW PERMISSION. PRIORITY - MAJOR
    Given I login as administrator
    And go to Products/ Products
    And click "Create Product"
    When I press "Continue"
    And I fill product name field with "Some Product" value
    Then I should see slug prototypes field filled with "some-product" value

  Scenario: "Product slug 1B" > CREATE PRODUCT WITHOUT SLUG VIEW PERMISSION. PRIORITY - MAJOR
    Given administrator permissions on View Slug is set to None
    And I fill product name field with "Some Other Product" value
    Then I should see slug prototypes field filled with "some-other-product" value
