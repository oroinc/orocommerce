@fixture-OroProductBundle:highlighting_new_products.yml
Feature: Brands
  In order to add sort products by brands
  As an Administrator
  I want to be able to create and assign brands to the product

  Scenario: Create different window session
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|

  Scenario: Create Brands
    Given I proceed as the Admin
    And login as administrator
    When go to Products/ Product Brands
    And click "Create Brand"
    And fill "Brand Form" with:
    |Name            |ACME            |
    |Status          |Enable          |
    |Meta Title      |apple           |
    |Meta Description|Meta Description|
    |Meta Keywords   |MetaKeyword     |
    And save and close form
    Then I should see "Brand has been saved" flash message
    And go to Products/ Product Brands
    And click "Create Brand"
    And fill "Brand Form" with:
      |Name            |Second Brand    |
      |Status          |Enable          |
      |Meta Title      |banana          |
      |Meta Description|Meta Description|
      |Meta Keywords   |Pineapple       |
    And save and close form
    Then I should see "Brand has been saved" flash message

  Scenario: Assign Brand to the product
    Given I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When click "Brand humburger button"
    And filter Brand as Contains "ACME"
    Then I should see following grid:
      |Brand|
      |ACME |
    And should not see "Second Brand"
    And click on ACME in grid
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check Brand visibility for the product view page
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When click "View Details" for "PSKU1" product
    Then I should see "ACME"
    And should not see "Second Brand"
    And I click "NewCategory"
    When click "View Details" for "PSKU2" product
    Then I should not see "ACME"
    And should not see "Second Brand"
    And I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When fill form with:
      |Brand|Second Brand|
    And save and close form
    And I should see "Product has been saved" flash message
    And I proceed as the User
    And I click "NewCategory"
    And click "View Details" for "PSKU1" product
    Then I should not see "ACME"
    And should see "Second Brand"
    And I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When click "Clear Brand"
    And save and close form
    And I should see "Product has been saved" flash message
    And I proceed as the User
    And reload the page
    Then I should not see "ACME"
    And should not see "Second Brand"

  Scenario: Search by Brand
    Given I proceed as the Admin
    And go to Products/ Products
    And click edit "PSKU1" in grid
    When fill form with:
      |Brand|Second Brand|
    And save and close form
    And I should see "Product has been saved" flash message
    And go to Products/ Products
    And click edit "PSKU2" in grid
    When fill form with:
      |Brand|ACME|
    And save and close form
    And I should see "Product has been saved" flash message
    And I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "NewCategory"
    When type "Second Brand" in "search"
    And click "Search Button"
    Then I should see "PSKU1"
    And I should not see "PSKU2"

