@regression
@fixture-OroRFPBundle:RFQWorkflows.yml
Feature: Frontend workflow configuration
  In order to provide button title different from the transition name
  As an Administrator
  I want to have an optional field (name for transition, label for transition button and
    title for transition button) in workflow management UI to provide button titles for transitions

  Scenario: Prepare Test Workflow
    Given I login as administrator
    Then I go to System/ Workflows

    And I click "Create Workflow"
    And I fill form with:
      | Name           | Workflow Button Titles |
      | Related Entity | Request For Quote      |

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with only Name |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Message and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Message and Title |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Message and Title and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Attribute |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Attribute and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Attribute and Title |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Attribute and Title and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Message, Attribute, Title and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Original Page with only Name |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with View Page and Message and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Index Page and Message and Title |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Original Page and Message and Title and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with View Page and Attribute |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Index Page and Attribute and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Original Page and Attribute and Title |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with View Page and Attribute and Title and Label |
    And I click "Apply"

    And I click "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step with Index Page and Message, Attribute, Title and Label |
    And I click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Start Transition    |
      | From step       | (Start)             |
      | To step         | Step with only Name |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name      | Popup transition with only name |
      | From step | Step with only Name             |
      | To step   | Step with only Name             |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Popup transition with Message and Label |
      | From step       | Step with only Name                     |
      | To step         | Step with Message and Label             |
      | Warning message | warning message text TML                |
      | Button Label    | Label TML                               |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Popup transition with Message and Title |
      | From step       | Step with Message and Label             |
      | To step         | Step with Message and Title             |
      | Warning message | warning message text TMT                |
      | Button Title    | Title TMT                               |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Popup transition with Message and Title, Label |
      | From step       | Step with Message and Title                    |
      | To step         | Step with Message and Title and Label          |
      | Warning message | warning message text TMTL                      |
      | Button Label    | Label TMTL                                     |
      | Button Title    | Title TMTL                                     |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name      | Popup transition with Attribute       |
      | From step | Step with Message and Title and Label |
      | To step   | Step with Attribute                   |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name         | Popup transition with Attribute and Label |
      | From step    | Step with Attribute                       |
      | To step      | Step with Attribute and Label             |
      | Button Label | Label TAL                                 |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name         | Popup transition with Attribute and Title |
      | From step    | Step with Attribute and Label             |
      | To step      | Step with Attribute and Title             |
      | Button Title | Title TAT                                 |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name         | Popup transition with Attribute and Title and Label |
      | From step    | Step with Attribute and Title                       |
      | To step      | Step with Attribute and Title and Label             |
      | Button Label | Label TATL                                          |
      | Button Title | Title TATL                                          |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Popup transition with Attribute and Title and Label |
      | From step       | Step with Attribute and Title and Label             |
      | To step         | Step with Message, Attribute, Title and Label       |
      | Warning message | warning message text TMATL                          |
      | Button Label    | Label TMATL                                         |
      | Button Title    | Title TMATL                                         |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Original transition with only name       |
      | From step        | Step with Message, Attribute, Title and Label |
      | To step          | Step with Original Page with only Name        |
      | View form        | Separate page                                 |
      | Destination Page | Original Page                                 |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page View transition with Message and Label          |
      | From step        | Step with Original Page with only Name               |
      | To step          | Step with View Page and Message and Label            |
      | View form        | Separate page                                        |
      | Destination Page | Entity View Page                                     |
      | Warning message  | warning message text View Page and Message and Label |
      | Button Label     | Label TPML                                           |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Index transition with Message and Title          |
      | From step        | Step with View Page and Message and Label             |
      | To step          | Step with Index Page and Message and Title            |
      | View form        | Separate page                                         |
      | Destination Page | Entity Index Page                                     |
      | Warning message  | warning message text Index Page and Message and Title |
      | Button Title     | Title TPMT                                            |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Original transition with message and Title, Label             |
      | From step        | Step with Index Page and Message and Title                         |
      | To step          | Step with Original Page and Message and Title and Label            |
      | View form        | Separate page                                                      |
      | Destination Page | Original Page                                                      |
      | Warning message  | warning message text Original Page and Message and Title and Label |
      | Button Label     | Label TPMTL                                                        |
      | Button Title     | Title TPMTL                                                        |
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page View transition with Attribute                     |
      | From step        | Step with Original Page and Message and Title and Label |
      | To step          | Step with View Page and Attribute                       |
      | View form        | Separate page                                           |
      | Destination Page | Entity View Page                                        |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Index transition with Attribute and Label |
      | From step        | Step with View Page and Attribute              |
      | To step          | Step with Index Page and Attribute and Label   |
      | View form        | Separate page                                  |
      | Destination Page | Entity Index Page                              |
      | Button Label     | Label TPAL                                     |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Original transition with Attribute and Title |
      | From step        | Step with Index Page and Attribute and Label       |
      | To step          | Step with Original Page and Attribute and Title    |
      | View form        | Separate page                                      |
      | Destination Page | Original Page                                      |
      | Button Title     | Title TPAT                                         |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page View transition with Attribute and Title and Label |
      | From step        | Step with Original Page and Attribute and Title         |
      | To step          | Step with View Page and Attribute and Title and Label   |
      | View form        | Separate page                                           |
      | Destination Page | Entity View Page                                        |
      | Button Label     | Label TPATL                                             |
      | Button Title     | Title TPATL                                             |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number          |
      | Label        | Change a PO Number |
    And click "Add"
    And click "Apply"

    And I click "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name             | Page Index transition with Message, Attribute, Title and Label      |
      | From step        | Step with View Page and Attribute and Title and Label               |
      | To step          | Step with Index Page and Message, Attribute, Title and Label        |
      | View form        | Separate page                                                       |
      | Destination Page | Entity Index Page                                                   |
      | Warning message  | warning message text - Page and Message, Attribute, Title and Label |
      | Button Label     | Label TPMATL                                                        |
      | Button Title     | Title TPMATL                                                        |
    And click "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | PO Number |
    And click "Add"
    And click "Apply"

    And save and close form
    And click "Activate"
    And I click "Activate" in modal window
    # for now, in UI no way to change applications
    And I allow workflow "Workflow Button Titles" for "commerce" application
    # start workflow to see it on frontend
    And I go to Sales/ Requests For Quote
    And click View 0110 in grid
    And I click "Start Transition"

  Scenario: Check Button Title on the entity view with Name in the transition
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    And I click "Requests For Quote"
    And click View 0110 in grid
    Then I should see "Popup transition with only name" button with attributes:
      | title | ~ |
    When I click "Popup transition with only name"

  Scenario: Check Button Title on the entity view with Message and Label in the transition
    Then I should see "Label TML" button with attributes:
      | title | ~ |
    When I click "Label TML"
    Then I should see "UiWindow" with elements:
      | Title        | Label TML                |
      | Content      | warning message text TML |
      | okButton     | OK                       |
      | cancelButton | Cancel                   |
    And click "OK"

  Scenario: Check Button Title on the entity view with Message and Title in the transition
    Then I should see "Popup transition with Message and Title" button with attributes:
      | title | Title TMT |
    When I click "Popup transition with Message and Title"
    Then I should see "UiWindow" with elements:
      | Title        | Popup transition with Message and Title |
      | Content      | warning message text TMT                |
      | okButton     | OK                                      |
      | cancelButton | Cancel                                  |
    And click "OK"

  Scenario: Check Button Title on the entity view with Message and Title and Label in the transition
    Then I should see "Label TMTL" button with attributes:
      | title | Title TMTL |
    When I click "Label TMTL"
    Then I should see "UiWindow" with elements:
      | Title        | Label TMTL                |
      | Content      | warning message text TMTL |
      | okButton     | OK                        |
      | cancelButton | Cancel                    |
    And click "OK"

  Scenario: Check Button Title on the entity view with Attribute in the transition
    Then I should see "Popup transition with Attribute" button with attributes:
      | title | ~ |
    When I click "Popup transition with Attribute"
    Then I should see "UiDialog" with elements:
      | Title    | Popup transition with Attribute |
      | okButton | Submit                          |
    And click "Submit"

  Scenario: Check Button Title on the entity view with Attribute and Label in the transition
    Then I should see "Label TAL" button with attributes:
      | title | ~ |
    When I click "Label TAL"
    Then I should see "UiDialog" with elements:
      | Title    | Label TAL |
      | okButton | Submit    |
    And click "Submit"

  Scenario: Check Button Title on the entity view with Attribute and Title in the transition
    Then I should see "Popup transition with Attribute and Title" button with attributes:
      | title | Title TAT |
    When I click "Popup transition with Attribute and Title"
    Then I should see "UiDialog" with elements:
      | Title    | Popup transition with Attribute and Title |
      | okButton | Submit                                    |
    And click "Submit"

  Scenario: Check Button Title on the entity view with Attribute and Title and Label in the transition
    Then I should see "Label TATL" button with attributes:
      | title | Title TATL |
    When I click "Label TATL"
    Then I should see "UiDialog" with elements:
      | Title    | Label TATL |
      | okButton | Submit     |
    And click "Submit"

  Scenario: Check Button Title on the entity view with Message, Attribute, Title and Label in the transition
    Then I should see "Label TMATL" button with attributes:
      | title | Title TMATL |
    When I click "Label TMATL"
    Then I should see "UiDialog" with elements:
      | Title    | Label TMATL                |
      | Content  | warning message text TMATL |
      | okButton | Submit                     |
    And click "Submit"

  Scenario: Check Button Title on the entity view with Original Page and only Name in the transition
    Then I should see "Page Original transition with only name" button with attributes:
      | title | ~ |
    When I click "Page Original transition with only name"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Page Original transition with only name"
    And click "Submit"

  Scenario: Check Button Title on the entity view with View Page and Message and Label in the transition
    Then I should see "Label TPML" button with attributes:
      | title | ~ |
    When I click "Label TPML"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Label TPML"
    And I should see "warning message text View Page and Message and Label"
    And click "Submit"

  Scenario: Check Button Title on the entity view with Index Page and Message and Title in the transition
    Then I should see "Page Index transition with Message and Title" button with attributes:
      | title | Title TPMT |
    When I click "Page Index transition with Message and Title"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Page Index transition with Message and Title"
    And I should see "warning message text Index Page and Message and Title"
    And click "Submit"

  Scenario: Check Button Title on the entity view with Original Page and Message and Title and Label in the transition
    Then I should see "Label TPMTL" button with attributes:
      | title | Title TPMTL |
    When I click "Label TPMTL"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Label TPMTL"
    And I should see "warning message text Original Page and Message and Title and Label"
    And click "Submit"

  Scenario: Check Button Title on the entity view with View Page and Attribute in the transition
    Then I should see "Page View transition with Attribute" button with attributes:
      | title | ~ |
    When I click "Page View transition with Attribute"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Page View transition with Attribute"
    And click "Submit"

  Scenario: Check Button Title on the entity view with Index Page and Attribute and Label in the transition
    Then I should see "Label TPAL" button with attributes:
      | title | ~ |
    When I click "Label TPAL"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Label TPAL"
    And click "Submit"

  Scenario: Check Button Title on the entity view with Original Page and Attribute and Title in the transition
    Then I should see "Page Original transition with Attribute and Title" button with attributes:
      | title | Title TPAT |
    When I click "Page Original transition with Attribute and Title"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Page Original transition with Attribute and Title"
    And click "Submit"

  Scenario: Check Button Title on the entity view with View Page and Attribute and Title and Label in the transition
    Then I should see "Label TPATL" button with attributes:
      | title | Title TPATL |
    When I click "Label TPATL"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Label TPATL"
    And click "Submit"

  Scenario: Check Button Title on the entity view with Index Page and Message, Attribute, Title and Label in the transition
    Then I should see "Label TPMATL" button with attributes:
      | title | Title TPMATL |
    When I click "Label TPMATL"
    Then I should see that "Workflow Page Title" contains "Workflow Button Titles / Label TPMATL"
    And I should see "warning message text - Page and Message, Attribute, Title and Label"
    And click "Submit"
