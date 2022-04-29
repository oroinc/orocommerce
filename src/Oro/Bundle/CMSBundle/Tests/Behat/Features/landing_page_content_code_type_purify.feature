@ticket-BB-19730
@fixture-OroCMSBundle:CMSPageWithCodeType.yml

Feature: Landing page content code type purify

  Scenario: Check that the code type component text is stored correctly with invalid multiline html
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click edit "Landing page" in grid
    When I fill the code type block containing the text "Type code here" with the value "<code>\n<div>Code line 1</div>\n<div>Code line 2</div>\n</code>"
    And save and close form

    Then I should see "Page has been saved" flash message
    And should see text matching "<code>"
    And should see text matching "<div>Code line 1</div>"
    And should see text matching "<div>Code line 2</div>"
    And should see text matching "</code>"

  Scenario: Check the code type component correctly render html code
    Given I click "Edit"
    Then I should see text matching "<code>" in WYSIWYG editor
    And should see text matching "<div>Code line 1</div>" in WYSIWYG editor
    And should see text matching "<div>Code line 2</div>" in WYSIWYG editor
    And should see text matching "</code>" in WYSIWYG editor

  Scenario: Check if the text in the code editor is displayed correctly
    Given I open code editor of code type block containing the text "code"
    Then I should see "<code>" in the "WysiwygCodeTypeBlockEditor" element
    And should see "<div>Code line 1</div>" in the "WysiwygCodeTypeBlockEditor" element
    And should see "<div>Code line 2</div>" in the "WysiwygCodeTypeBlockEditor" element
    And should see "</code>" in the "WysiwygCodeTypeBlockEditor" element
