@regression
Feature: WYSIWYG table type component

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add new table into canvas
    When I add new component "Table" from panel to editor area
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="table-responsive"> |
      | 2  | <table class="table">          |
      | 3  | <thead>                        |
      | 6  | <div>Header Cell               |
      | 19 | <tbody>                        |
      | 22 | <div>Body Cell                 |
    And I clear canvas in WYSIWYG

  Scenario: Check add rows and cells
    When I add new component "Table" from panel to editor area
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 2 |
    Then I apply "insert-row-after" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="table-responsive"> |
      | 2  | <table class="table">          |
      | 20 | <tr>                           |
      | 34 | <tr>                           |
      | 48 | <tr>                           |
      | 49 | <td>                           |
      | 53 | <td>                           |
      | 57 | <td>                           |
    Then I apply "insert-row-before" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="table-responsive"> |
      | 2  | <table class="table">          |
      | 20 | <tr>                           |
      | 34 | <tr>                           |
      | 48 | <tr>                           |
      | 62 | <tr>                           |
      | 49 | <td>                           |
      | 53 | <td>                           |
      | 57 | <td>                           |
      | 71 | <td>                           |
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 3 |
      | cell             | 3 |
    Then I apply "insert-column-after" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 14 | <div>Header Cell |
      | 38 | <div>Body Cell   |
      | 56 | <div>Body Cell   |
      | 74 | <div>Body Cell   |
    Then I apply "insert-column-before" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 14 | <div>Header Cell |
      | 38 | <div>Body Cell   |
      | 56 | <div>Body Cell   |
      | 74 | <div>Body Cell   |

  Scenario: Delete row and cells
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 2 |
    Then I apply "delete-column" action in RTE
    And I apply "delete-column" action in RTE
    And I apply "delete-row" action in RTE
    And I apply "delete-row" action in RTE
    And I apply "delete-column" action in RTE
    And I apply "delete-row" action in RTE
    And I apply "delete-column" action in RTE
    And I apply "delete-column" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <style> |
    And I clear canvas in WYSIWYG

  Scenario: Clone table
    When I add new component "Table" from panel to editor area
    And I select component in canvas by tree:
      | table-responsive | 1 |
    And I click "WysiwygToolbarActionClone"
    Then I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="table-responsive"> |
      | 2  | <table class="table">          |
      | 19 | <tbody>                        |
      | 51 | <div class="table-responsive"> |
      | 52 | <table class="table">          |
      | 69 | <tbody>                        |
    And I clear canvas in WYSIWYG

  Scenario: Move table
    When I add new component "2 Columns" from panel to editor area
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
    And I add new component "Table" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
    Then I check wysiwyg content in "CMS Page Content":
      | 5 | <div class="table-responsive"> |
    And I select component in canvas by tree:
      | grid-row         | 1 |
      | grid-column      | 2 |
      | table-responsive | 1 |
    And I move "SelectedComponent" to "FirstColumnInGrid" in editor canvas
    Then I check wysiwyg content in "CMS Page Content":
      | 3 | <div class="table-responsive"> |
    And I clear canvas in WYSIWYG

  Scenario: Add content to table
    When I add new component "Table" from panel to editor area
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 2 |
      | text             | 1 |
    And I click "WysiwygToolbarActionDelete"
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 2 |
    And I add new component "Quote" from panel to:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 2 |
    Then I check wysiwyg content in "CMS Page Content":
      | 25 | <td>                                                                                                                                          |
      | 26 | <blockquote class="quote">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore ipsum dolor sit |
      | 27 | </blockquote>                                                                                                                                 |
      | 28 | </td>                                                                                                                                         |
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 1 |
      | text             | 1 |
    And I click "WysiwygToolbarActionDelete"
    And I select component in canvas by tree:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 1 |
    And I click "OpenBlocksTab"
    And I add new component "Text Section" from panel to:
      | table-responsive | 1 |
      | table            | 1 |
      | tbody            | 1 |
      | row              | 1 |
      | cell             | 1 |
    Then I check wysiwyg content in "CMS Page Content":
      | 21 | <td>                                                                                                                         |
      | 22 | <section>                                                                                                                    |
      | 23 | <h1>Insert title here                                                                                                        |
      | 24 | </h1>                                                                                                                        |
      | 25 | <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididuntut labore et dolore magna aliqua |
      | 26 | </p>                                                                                                                         |
      | 27 | </section>                                                                                                                   |
      | 28 | </td>                                                                                                                        |
    And I clear canvas in WYSIWYG

