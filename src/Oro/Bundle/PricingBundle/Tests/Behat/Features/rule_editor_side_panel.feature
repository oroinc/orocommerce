@regression
@ticket-BAP-22279

Feature: Rule Editor Side Panel

  Scenario: Checking operators from side panel
    Given I login as administrator
    And I go to Sales/ Price
    Then I click "Create Price List"
    And I click "Equal"
    And I click "Greater Than"
    And I click "Greater Than or Equal"
    And I click "Not Equal"
    And I click "Less Than"
    And I click "Less Than or Equal"
    And I click "Addition"
    And I click "Multiplication"
    And I click "Remainder"
    And I click "Subtraction"
    And I click "Division"
    And I click "Parentheses"
    And I click "In"
    And I click "Not in"
    And I click "And"
    And I click "Or"
    And I click "Yes"
    And I click "No"
    And I click "Match"
    And I click "Not match"
    And I click "Empty"
    And I click "Not empty"
    Then Product Assignment Rule field should has == > >= != < <= + * % - / (in [not in [and or matches containsRegExp(not matches containsRegExp( != 0 == 0)) == false == true]]) value

  Scenario: Checking build expression just side panel
    Given I clear text in "Product Assignment Rule Editor"
    And I click "Field"
    And I select "Price List" option in selection results
    And I select "Created At" option in selection results
    Then I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    And I click "Less Than"
    And I type "'12-12-2020' " in "Product Assignment Rule Editor"
    And I click "And"
    And I click "Field"
    And I select "Price List" option in selection results
    And I select "Id" option in selection results
    Then I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    And I click "In"
    And I type "1, 2, 3" in "Product Assignment Rule Editor"
    Then Product Assignment Rule field should has pricelist[1].createdAt < '12-12-2020' and pricelist[1].id in [1, 2, 3] value

  Scenario: Checking build function expression just side panel
    Given I clear text in "Product Assignment Rule Editor"
    And I click "Parentheses"
    And I click "Field"
    And I select "Product" option in selection results
    And I select "SKU" option in selection results
    And I click "Match"
    And I type "'Test SKU'" in "Product Assignment Rule Editor"
    Then Product Assignment Rule field should has (product.sku matches containsRegExp('Test SKU')) value
    Given I clear text in "Product Assignment Rule Editor"
    And I click "Yes"
    And I click "Field"
    And I select "Product" option in selection results
    And I select "New Arrival" option in selection results
    Then Product Assignment Rule field should has product.newArrival  == true value
