@ticket-BB-18497
@fixture-OroProductBundle:product_search/products.yml

Feature: Administrator should have a possibility to remove website without affecting of the products indexed in the
  scope of the default website

  Scenario: Feature Background
    Given sessions active:
      | Admin   | first_session  |
      | User    | second_session |

  Scenario: Check the search results match the specified criteria (search text).
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    And I type "Description3" in "search"
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1

  Scenario: Administrator create and remove website
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Websites
    And I click "Create Website"
    And fill form with:
      | Name                           | Foo Website   |
      | Default Self-Registration Role | Administrator |
      | Guest Role                     | Administrator |
    Then I save and close form
    Then I go to System / Websites
    And I click "Delete" on row "Foo Website" in grid
    And I click "Yes, Delete" in confirmation dialogue

  Scenario: Check the search found products after website was removed
    Given I proceed as the User
    And I go to the homepage
    And I type "Description3" in "search"
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
