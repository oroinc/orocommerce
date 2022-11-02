@regression
@fixture-OroSaleBundle:QuotesGrid.yml
@ticket-BAP-17648

Feature: Quotes Grid

  In order to ensure backoffice All Quotes Grid works correctly
  As an administrator
  I check filters are working, sorting is working and columns config is working as designed.

  Scenario: Check Quote qid filter
    Given I login as administrator
    And I go to Sales / Quotes
    When I filter "Quote #" as Contains "Quote2"
    Then I should see following grid:
      | Quote # |
      | Quote2  |
      | Quote20 |
    And records in grid should be 2
    And I reset "Quote #" filter

  Scenario: Check Owner filter
    Given records in grid should be 20
    When I filter Owner as Contains "charlie"
    Then I should see following grid:
      | Quote # |
      | Quote6  |
    And records in grid should be 1
    And I reset Owner filter

  Scenario: Check Customer User filter
    Given records in grid should be 20
    When I filter Customer User as Contains "test2"
    Then I should see following grid:
      | Quote # |
      | Quote7  |
    And records in grid should be 1
    And I reset Customer User filter

  Scenario: Check Customer filter
    Given records in grid should be 20
    When I filter Customer: All as Contains "NoCustomerUser" in "Grid" grid strictly
    Then I should see following grid:
      | Quote # |
      | Quote8  |
    And records in grid should be 1
    And I reset "Customer: NoCustomerUser" filter

  Scenario: Check Internal Status filter
    Given records in grid should be 20
    When I choose filter for Internal Status as Is Any Of "Draft"
    Then I should not see "Quote1 "
    And records in grid should be 19
    And I reset Internal Status filter

  Scenario: Check Customer Status filter
    Given records in grid should be 20
    When I choose filter for Customer Status as Is Any Of "Not Approved"
    Then I should see following grid:
      | Quote # |
      | Quote10 |
    And records in grid should be 1
    And I reset Customer Status filter

  Scenario: Check Expired filter
    Given records in grid should be 20
    When I check "Yes" in "Expired: All" filter strictly
    Then I should see following grid:
      | Quote # |
      | Quote11 |
    And records in grid should be 1
    And I reset "Expired: Yes" filter

  Scenario: Check Valid Until filter
    Given records in grid should be 20
    When I filter Valid Until as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Valid Until as between "today" and "today+1"
    Then I should see following grid:
      | Quote # |
      | Quote12 |
    And records in grid should be 1
    And I reset "Valid Until" filter

  Scenario: Check PO Number filter
    Given records in grid should be 20
    When I filter PO Number as Contains "PO16"
    Then I should see following grid:
      | Quote # |
      | Quote16 |
    And records in grid should be 1
    And I reset "PO Number" filter

  Scenario: Check Ship Until filter
    Given records in grid should be 20
    When I filter Do Not Ship Later Than as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Do Not Ship Later Than as between "today" and "today+1"
    Then I should see following grid:
      | Quote # |
      | Quote13 |
    And records in grid should be 1
    And I reset "Do Not Ship Later Than" filter

  Scenario: Check Created At filter
    Given records in grid should be 20
    When I filter Created At as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Created At as between "today" and "today+1"
    Then records in grid should be 20
    And I reset "Created At" filter

  Scenario: Check Updated At filter
    Given records in grid should be 20
    When I filter Updated At as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Updated At as between "today" and "today+1"
    Then records in grid should be 20
    And I reset "Updated At" filter

  Scenario: Enable & Check Payment Term filter
    Given records in grid should be 20
    And I show filter "Payment Term" in "All Quotes Grid" grid
    When I check "Net 10" in "Payment Term: All" filter strictly
    Then I should see following grid:
      | Quote # |
      | Quote14 |
    And records in grid should be 1
    And I reset "Payment Term: net 10" filter

  Scenario: Check Step filter
    Given records in grid should be 20
    When I check "Submitted for Review" in "Step: All" filter strictly
    Then I should see following grid:
      | Quote # |
      | Quote1  |
    And records in grid should be 1

  Scenario: Check Filter Applies After Different Actions
    Given I hide column Step in grid
    Then I should see following grid:
      | Quote # |
      | Quote1 |
    And records in grid should be 1
    And I reset "Step: Submitted for Review" filter
    When I check "Draft" in "Step: All" filter strictly
    Then I should not see "Quote1 "
    And records in grid should be 19
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should not see "Quote1 "
    When I press next page button
    Then records in grid should be 9
    When I refresh "All Quotes Grid" grid
    Then records in grid should be 9
    When I reload the page
    Then records in grid should be 9
    When I hide filter "Step" in "All Quotes Grid" grid
    Then there is 20 records in grid
    When I reset "All Quotes Grid" grid
    Then there is 20 records in grid
    And records in grid should be 20

  Scenario: Sort by Quote #
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Quote #"
    Then I should see following grid:
      | Quote # |
      | Quote9  |
      | Quote8  |
    When I sort grid by "Quote #" again
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Customer User
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Customer User"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Customer User" again
    Then I should see following grid:
      | Quote # |
      | Quote8  |
      | Quote7  |
      | Quote20 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Customer
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    And I hide column Customer in grid
    When I sort grid by "Customer"
    Then I should see following grid:
      | Quote # |
      | Quote8  |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Customer" again
    Then I should see following grid:
      | Quote # |
      | Quote20  |
      | Quote19 |
      | Quote18 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Internal Status
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Internal Status"
    Then I should see following grid:
      | Quote # |
      | Quote2  |
      | Quote3  |
    When I sort grid by "Internal Status" again
    Then I should see following grid:
      | Quote # |
      | Quote1 |
      | Quote20 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Owner
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Owner"
    Then I should see following grid:
      | Quote # |
      | Quote6  |
      | Quote1  |
    When I sort grid by "Owner" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by PO Number
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "PO Number"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "PO Number" again
    Then I should see following grid:
      | Quote # |
      | Quote9 |
      | Quote8 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Created At
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Created At"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Created At" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Updated At
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Updated At"
    Then I should see following grid:
      | Quote # |
      | Quote1 |
      | Quote2  |
    When I sort grid by "Updated At" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Expired
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Expired"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Expired" again
    Then I should see following grid:
      | Quote # |
      | Quote11 |
      | Quote20 |

  Scenario: Check Sorter Applies After Different Actions
    Given I hide column Expired in grid
    Then I should see following grid:
      | Quote # |
      | Quote11 |
      | Quote20 |
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should see following grid:
      | Quote # |
      | Quote11 |
      | Quote20 |
    When I press next page button
    Then I should see following grid:
      | Quote # |
      | Quote10 |
      | Quote9  |
    When I reload the page
    Then I should see following grid:
      | Quote # |
      | Quote10 |
      | Quote9  |
    When I reset "All Quotes Grid" grid
    Then there is 20 records in grid
    And records in grid should be 20

  Scenario: Check columns are loaded correctly
    Given I hide all columns in grid except Quote #
    When I show column Owner in grid
    Then I should see "Owner" column in grid
    And I should see following grid with exact columns order:
      | Quote # | Owner    |
      | Quote1  | John Doe |
    When I show column Step in grid
    Then I should see "Step" column in grid
    And I should see following grid with exact columns order:
      | Quote # | Owner    | Step                 |
      | Quote1  | John Doe | Submitted for Review |

  Scenario: Check Columns Config Applies After Different Actions
    Given records in grid should be 20
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should see following grid with exact columns order:
      | Quote # | Owner    | Step                 |
      | Quote1  | John Doe | Submitted for Review |
      | Quote10 | John Doe | Draft                |
    When I press next page button
    And I should see following grid with exact columns order:
      | Quote # | Owner    | Step  |
      | Quote19 | John Doe | Draft |
      | Quote2  | John Doe | Draft |
    When I reload the page
    And I should see following grid with exact columns order:
      | Quote # | Owner    | Step  |
      | Quote19 | John Doe | Draft |
      | Quote2  | John Doe | Draft |
    When I reset "All Quotes Grid" grid
    Then I should see following grid with exact columns order:
      | Quote # | Owner    | Customer User | Customer         |
      | Quote1  | John Doe | Test1 Test1   | WithCustomerUser |
      | Quote10 | John Doe | Test1 Test1   | WithCustomerUser |

  Scenario: Check Grid View is Saved and Restored Properly
    Given I hide all columns in grid except Quote #
    And I show column Owner in grid
    And I sort grid by "Owner"
    And I filter "Quote #" as Contains "Quote6"
    When I click Options in grid view
    And I click on "Save As" in grid view options
    And I type "gridview1" in "name"
    And I click "Save" in modal window
    Then I should see "View has been successfully created" flash message
    And I reset "All Quotes Grid" grid
    Then I should see following grid with exact columns order:
      | Quote # | Owner    | Customer User | Customer         |
      | Quote1  | John Doe | Test1 Test1   | WithCustomerUser |
      | Quote10 | John Doe | Test1 Test1   | WithCustomerUser |
    When I click grid view list
    And I click "gridview1"
    Then I should see following grid with exact columns order:
      | Quote # | Owner         |
      | Quote6  | Charlie Sheen |
    When I click Options in grid view
    And I click on "Set as default" in grid view options
    Then I should see "View has been successfully updated" flash message
    When I go to Sales / Quotes
    Then I should see "Gridview1"
    And I should see following grid with exact columns order:
      | Quote # | Owner         |
      | Quote6  | Charlie Sheen |
    When I click Options in grid view
    And I click on "Delete" in grid view options
    And I confirm deletion
    Then I should see "View has been successfully deleted" flash message
    And I should see following grid with exact columns order:
      | Quote # | Owner    | Customer User | Customer         |
      | Quote1  | John Doe | Test1 Test1   | WithCustomerUser |
      | Quote10 | John Doe | Test1 Test1   | WithCustomerUser |
