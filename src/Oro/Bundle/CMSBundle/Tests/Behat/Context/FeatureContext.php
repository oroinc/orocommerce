<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\CMSBundle\Tests\Behat\Element\WysiwygCodeTypeBlockEditor;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ?OroMainContext $oroMainContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @When /^(?:|I )type "(?P<value>(?:[^"]|\\")*)" in Landing Page Titles field$/
     */
    public function typeInLandingPageTitlesField(string $value): void
    {
        $productNameField = $this->createElement('LandingPageTitlesField');
        $this->getDriver()->typeIntoInput($productNameField->getXpath(), $value);
    }

    /**
     * @When /^(?:|I )fill in Landing Page Titles field with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function fillInLandingPageTitlesFieldWith($value)
    {
        $productNameField = $this->createElement('LandingPageTitlesField');
        $productNameField->focus();
        $productNameField->setValue($value);
        $productNameField->blur();
        $this->waitForAjax();
    }

    /**
     * Example: When I import content "Content" to "CMS Page Content" WYSIWYG editor
     *
     * @When /^(?:|I )import content "(?P<text>(?:[^"]|\\")*)" to "(?P<wysiwygElementName>[^"]+)" WYSIWYG editor$/
     * @param string $text
     * @param string $wysiwygElementName
     */
    public function importContentToWysiwygEditor($text, $wysiwygElementName)
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $importDialog = $this->createElement('Import Button');
        $importDialog->click();

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $editor->setValue(stripslashes($text));
        $this->waitForAjax();
        $editor->findButton('Apply Changes')->click();
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * Example: When I should see imported "Content" content in "CMS Page Content" WYSIWYG editor
     *
     * @When /^(?:|I )should see imported "(?P<text>(?:[^"]|\\")*)" content in "(?P<wysiwygElementName>[^"]+)" WYSIWYG editor$/
     * @param string $text
     * @param string $wysiwygElementName
     * @param string $importDialogName
     *
     * @codingStandardsIgnoreEnd
     */
    public function shouldSeeImportedContent($text, $wysiwygElementName, $importDialogName = "Import Button")
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $importDialog = $this->createElement($importDialogName);
        $importDialog->click();

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $importedContent = str_replace("\n", "", $editor->getValue());
        self::assertEquals(stripslashes($text), $importedContent);

        $editor->findButton('Apply Changes')->click();
    }

    /**
     * Example: I open code editor of code type block containing the text "Same text 1"
     *
     * @When /^(?:|I )open code editor of code type block containing the text "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function openCodeEditorOfCodeTypeBlockContainingTheText(string $value): void
    {
        $codeTypeBlockElement = $this->findCodeTypeBlock($value);
        $this->openCodeTypeBlockEditor($codeTypeBlockElement);
    }

    /**
     * Example: I fill the code type block containing the text "Same text 1" with the value "Same text 2"
     *
     * @codingStandardsIgnoreStart
     *
     * @When /^(?:|I )fill the code type block containing the text "(?P<existingValue>(?:[^"]|\\")*)" with the value "(?P<newValue>(?:[^"]|\\")*)"$/
     *
     * @codingStandardsIgnoreEnd
     */
    public function fillTheCodeTypeBlockContainingTheTextWithTheValue(string $existingValue, string $newValue): void
    {
        $codeTypeBlockElement = $this->findCodeTypeBlock($existingValue);
        $this->openCodeTypeBlockEditor($codeTypeBlockElement);

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $editor->setValue($newValue);
        $editor->findButton('Save')->click();
    }

    private function findCodeTypeBlock(string $containingValue): Element
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        /**
         * Wait for WYSIWYG editor is initialized.
         *
         * @var Element $element
         */
        $element = $this->spin(function () use ($containingValue) {
            $element = $this->findElementContains('WysiwygCodeTypeBlock', $containingValue);

            return $element->isIsset() ? $element : false;
        }, 3);
        self::assertNotNull($element, sprintf('Wysiwyg `code` type block with text "%s" not found!', $containingValue));

        // Since the modal window is displayed outside the iframe, switch to the main DOM.
        $this->getDriver()->switchToIFrame();

        return $element;
    }

    private function openCodeTypeBlockEditor(Element $codeTypeBlockElement): void
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        // Since we use the selected block to display the content in the Mirror editor, it is necessary to
        // simulate the entire cycle of opening a modal window, namely its selection(one click)
        // and open editor ('one click' and 'double click' events).
        $codeTypeBlockElement->click();
        $codeTypeBlockElement->doubleClick();

        // Since the modal window is displayed outside the iframe, switch to the main DOM.
        $this->getDriver()->switchToIFrame();
    }

    /**
     * Add component to canvas via drag and drop method
     *
     * @param string $blockName
     * @param boolean $toSelected
     * @param boolean $byClick
     */
    private function addComponentToEditor(string $blockName, $toSelected = false, $byClick = false): void
    {
        $blockPanel = $this->createElement('BlocksPanel');
        $blockPanelBtn = $this->createElement('OpenBlocksTab');

        if (!$blockPanelBtn->hasClass('gjs-pn-active')) {
            $blockPanelBtn->click();
        }

        $block = $blockPanel->find(
            'css',
            sprintf('.gjs-block[title="%s"]', $blockName)
        );

        self::assertTrue($block->isValid(), sprintf('GrapesJs block "%s" not found on page', $blockName));

        if ($byClick) {
            $block->click();
        } else {
            $this->dragAndDropTheBlockToGrapesJs($block, $toSelected ? 'SelectedComponent' : '');
        }
    }

    /**
     * @Then /^(?:|I )should see following content templates categories in GrapesJs:$/
     */
    public function shouldSeeTheFollowingContentTemplatesCategories(TableNode $table): void
    {
        $categories = $this->findAllElements('GrapesJs Content Templates Category');
        self::assertNotEmpty(
            $categories,
            'Content template categories "GrapesJs Content Templates Category" not found'
        );

        $expectedCategories = $table->getColumn(0);
        foreach ($categories as $index => $eachCategory) {
            $categoryTitle = $this->createElement('GrapesJs Content Templates Category Title', $eachCategory);
            self::assertTrue(
                $categoryTitle->isIsset(),
                'Category title element "GrapesJs Content Templates Category Title" not found on page'
            );

            self::assertEquals(trim($expectedCategories[$index]), trim($categoryTitle->getText()));
        }
    }

    /**
     * @Then /^(?:|I )there are (?P<count>(?:\d+)) content templates? in category "(?P<category>(?:[^"]|\\")*)"$/
     */
    public function thereAreCountContentTemplatesInCategory(int $count, string $category): void
    {
        $categories = $this->findAllElements('GrapesJs Content Templates Category');
        self::assertNotEmpty(
            $categories,
            'Content template categories "GrapesJs Content Templates Category" not found'
        );

        $foundCategory = null;
        foreach ($categories as $eachCategory) {
            $categoryTitle = $this->createElement('GrapesJs Content Templates Category Title', $eachCategory);
            self::assertTrue(
                $categoryTitle->isIsset(),
                'Category title element "GrapesJs Content Templates Category Title" not found on page'
            );

            if (trim($category) === trim($categoryTitle->getText())) {
                $contentTemplates = $this->findAllElements('GrapesJs Content Template', $eachCategory);
                self::assertCount(
                    $count,
                    $contentTemplates,
                    sprintf(
                        '%d content templates expected in "%s" content template category, got %d',
                        $count,
                        $category,
                        count($contentTemplates)
                    )
                );

                return;
            }
        }

        self::assertNotNull($foundCategory, sprintf('Content template category "%s" not found', $category));
    }

    /**
     * Example: I add "Text content" to dialog code editor
     *
     * @When /^(?:|I )add "(?P<text>(?:[^"]|\\")*)" to dialog code editor$/
     * @When /^(?:|I )add "(?P<text>(?:[^"]|\\")*)" to dialog code editor with (?P<save>(?:[^"]|\\")*)$/
     *
     * @param string $text
     * @param string $save
     *
     * @return void
     */
    public function addContentToDialogCodeEditor(string $text, string $save = ''): void
    {
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $editor->setValue(stripslashes($text));

        $this->waitForAjax();

        $this->spin(function () use ($save, $editor) {
            if ($save) {
                $btn = $editor->findButton($save);
                self::assertTrue($btn->isValid(), sprintf('Button "%s" not found on page', $save));

                $btn->click();
            }
        }, 1);
    }

    /**
     * Example: I update selected component "Decoration" styles:
     *  | StyleName1 | StyleValue1 |
     *  | StyleName2 | StyleValue2 |
     *
     * @When /^(?:|I )update selected component "(?P<sector>(?:[^"]|\\")*)" styles:$/
     *
     * @param string $sector
     * @param TableNode $table
     */
    public function updateComponentStyles(string $sector, TableNode $table): void
    {
        $layerTabBtn = $this->createElement('OpenStyleTab');
        if (!$layerTabBtn->hasClass('gjs-pn-active')) {
            $layerTabBtn->click();
        }

        $page = $this->getPage();

        $sector = $page->find(
            'css',
            '.gjs-sm-sector__' . str_replace(' ', '-', strtolower($sector))
        );
        if (!$sector->hasClass('gjs-sm-open')) {
            $sector->click();
        }

        foreach ($table->getRows() as $row) {
            [$field, $value] = $row;

            $fieldWrap = $page->find(
                'css',
                sprintf('.gjs-sm-property__%s', strtolower($field))
            );
            $fieldElement = $fieldWrap->find('css', 'input, select');
            $fieldElement->setValue($value);
        }
    }

    /**
     * Example: I update selected component settings:
     *  | TraitName1 | TraitValue1 |
     *  | TraitName2 | TraitValue2 |
     *
     * @When /^(?:|I )update selected component settings:$/
     *
     * @param TableNode $table
     */
    public function updateComponentTrait(TableNode $table): void
    {
        $layerTabBtn = $this->createElement('OpenStyleTab');
        if (!$layerTabBtn->hasClass('gjs-pn-active')) {
            $layerTabBtn->click();
        }

        $page = $this->getPage();

        foreach ($table->getRows() as $row) {
            [$field, $value] = $row;

            $fieldName = lcfirst(str_replace(' ', '', ucwords(strtolower($field))));

            $fieldWrap = $page->find(
                'css',
                sprintf('.gjs-trt-trait__wrp-%s > .gjs-trt-trait', $fieldName)
            );

            $fieldElement = $fieldWrap->find('css', 'input, select');


            if ($fieldElement->getAttribute('type') === 'checkbox') {
                if ($fieldElement->isChecked() !== $value) {
                    $fieldElement->getParent()->click();
                }
                continue;
            }

            $fieldElement->setValue($value);
        }
    }

    /**
     * Example: I click on "Clone" action for selected component
     *
     * @When /^(?:|I )click on "(?P<toolName>(?:[^"]|\\")*)" action for selected component$/
     *
     * @param string $toolName
     */
    public function clickToolbarAction(string $toolName): void
    {
        $toolElement = $this->getPage()->find(
            'xpath',
            sprintf('//div[contains(@class, "gjs-toolbar-item") and @label="%s"]', $toolName)
        );

        self::assertTrue(
            $toolElement->isValid(),
            sprintf('Tool button "%s" not found on page', $toolName)
        );

        $toolElement->click();
    }

    /**
     * Example: I add new component "Text" by click from panel to:
     * | table-responsive | 1 |
     * | table            | 1 |
     * | tbody            | 1 |
     * | row              | 2 |
     * | cell             | 3 |
     *
     * Description: First column is "Component Type"
     *              Second column is "Order in list of types on current level"
     *
     * @When /^(?:|I )add new component "(?P<blockName>(?:[^"]|\\")*)" by click from panel to:$/
     *
     * @param string $blockName
     * @param TableNode $table
     */
    public function addNewComponentToByClick(string $blockName, TableNode $table): void
    {
        $this->selectComponentInCanvas($table);
        $this->addComponentToEditor($blockName, true, true);
    }

    /**
     * Example: I add new component "Text" from panel to:
     * | table-responsive | 1 |
     * | table            | 1 |
     * | tbody            | 1 |
     * | row              | 2 |
     * | cell             | 3 |
     *
     * Description: First column is "Component Type"
     *              Second column is "Order in list of types on current level"
     *
     * @When /^(?:|I )add new component "(?P<blockName>(?:[^"]|\\")*)" from panel to:$/
     *
     * @param string $blockName
     * @param TableNode $table
     */
    public function addNewComponentTo(string $blockName, TableNode $table): void
    {
        $this->selectComponentInCanvas($table);
        $this->addComponentToEditor($blockName, true);
    }

    /**
     * Example: I add new component "Text" from panel to editor area
     *
     * @When /^(?:|I )add new component "(?P<blockName>(?:[^"]|\\")*)" from panel to editor area$/
     *
     * @param string $blockName
     */
    public function addNewComponentFromPanel(string $blockName): void
    {
        $this->addComponentToEditor($blockName);
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @When /^(?:|I )drag and drop the block "(?P<blockElement>(?:[^"]|\\")*)" to the GrapesJs Wysiwyg Root Area$/
     * @When /^(?:|I )drag and drop the block "(?P<blockElement>(?:[^"]|\\")*)" to the "(?P<destinationElement>(?:[^"]|\\")*)"$/
     *
     * @codingStandardsIgnoreEnd
     */
    public function dragAndDropTheBlockToGrapesJs(
        object|string $blockElement,
        string $destinationElement = ''
    ): void {
        $sourceContextNodePath = '/';
        if (is_string($blockElement)) {
            $element = $this->createElement($blockElement);
            self::assertTrue($element->isIsset(), sprintf('GrapesJs block "%s" not found on page', $blockElement));
        }

        if (is_object($blockElement)) {
            $element = $blockElement;
        }

        $destinationContextNode = $this->createElement('GrapesJs Wysiwyg');
        self::assertTrue($destinationContextNode->isIsset(), 'GrapesJs block "GrapesJs Wysiwyg" not found on page');

        if ($destinationElement) {
            $destination = $this->createElement($destinationElement);
        } else {
            $destination = $this->createElement('GrapesJs Wysiwyg Root Area');
        }

        $snippet = file_get_contents(__DIR__ . '/Snippet/drag-and-drop.js');

        $this->getSession()
            ->getDriver()
            ->executeScript(
                sprintf(
                    '(%s)("%s", "%s", "%s", "%s");',
                    trim($snippet),
                    addslashes($element->getXPath()),
                    addslashes($destination->getXPath()),
                    addslashes($sourceContextNodePath),
                    addslashes($destinationContextNode->getXPath())
                )
            );
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @When /^(?:|I )move "(?P<blockElement>(?:[^"]|\\")*)" to "(?P<destinationElement>(?:[^"]|\\")*)" in editor canvas$/
     *
     * @param string $blockElement
     * @param string $destinationElement

     * @codingStandardsIgnoreEnd
     */
    public function moveComponentTo(string $blockElement, string $destinationElement): void
    {
        $this->getDriver()->switchToIFrame(0);

        $destinationContextNode = $this->createElement('GrapesJs Wysiwyg Root Area');
        self::assertTrue(
            $destinationContextNode->isIsset(),
            'GrapesJs block "GrapesJs Wysiwyg" not found on page'
        );

        $sourceContextNodePath = '/';
        $element = $this->createElement($blockElement);
        self::assertTrue(
            $element->isIsset(),
            sprintf('GrapesJs block "%s" not found on page', $blockElement)
        );

        $destination = $this->createElement($destinationElement);

        $snippet = file_get_contents(__DIR__ . '/Snippet/drag-and-drop.js');

        $this->getSession()
            ->getDriver()
            ->executeScript(
                sprintf(
                    '(%s)("%s", "%s", "%s", "%s");',
                    trim($snippet),
                    addslashes($element->getXPath()),
                    addslashes($destination->getXPath()),
                    addslashes($sourceContextNodePath),
                    addslashes($destinationContextNode->getXPath())
                )
            );

        $this->getDriver()->switchToWindow();
    }

    /**
     * Example: I enter "Test text" text to "TextComponent" component
     * @When /^(?:|I )enter "(?P<text>(?:[^"]|\\")*)" text to "(?P<selector>(?:[^"]|\\")*)" component$/
     *
     * @param string $selector
     * @param string $text
     */
    public function editTextComponentWith(string $text, string $selector): void
    {
        $this->getDriver()->switchToIFrame(0);

        $element = $this->createElement($selector);
        self::assertTrue(
            $element->isValid(),
            sprintf('Element "%s" not found in WYSIWYG editor', $selector)
        );
        $element->click();
        $element->doubleClick();

        $this->getDriver()->typeIntoInput($element->getXpath(), $text);
        $this->getDriver()->switchToWindow();
    }

    /**
     * Example: I put caret after "consectetur adipiscing elit" in selected component
     * Example: I select text "consectetur adipiscing elit" range in selected component
     * Example: I select all text in selected component
     *
     * @When /^(?:|I )put caret (?P<position>(?:[^"]|\\")*) "(?P<rangeText>(?:[^"]|\\")*)" in selected component$/
     * @When /^(?:|I )select text "(?P<rangeText>(?:[^"]|\\")*)" range in selected component$/
     * @When /^(?:|I )select all text in selected component$/
     *
     * @param string $rangeText
     */
    public function selectTextRange(string $rangeText = '', string $position = ''): void
    {
        $this->getDriver()->switchToIFrame(0);

        $snippet = file_get_contents(__DIR__ . '/Snippet/select-text.js');

        $this->getDriver()->executeScript(
            sprintf(
                '(%s)("%s", "%s");',
                trim($snippet),
                $rangeText,
                $position
            )
        );

        $this->getDriver()->switchToIFrame();
    }

    /**
     * Example: I check wysiwyg content in "CMS Page Content":
     *
     * @When /^(?:|I )check wysiwyg content in "(?P<wysiwygElementName>[^"]+)":$/
     *
     * @param string $wysiwygElementName
     * @param TableNode $table
     */
    public function checkWysiwygContent(string $wysiwygElementName, TableNode $table): void
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $importDialog = $this->createElement("Import Button");
        $importDialog->click();

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $importedContent = explode("\n", $editor->getValue());

        foreach ($table->getRows() as $row) {
            [$key, $value] = $row;

            $line = preg_replace(
                '/\ssrcset="([\w\d\/\-\.]+)"|\ssrc="([\w\d\/\-\.]+)"|\sid="([\d\w\-]+)"/',
                '',
                str_replace(' ', ' ', trim($importedContent[$key - 1]))
            );
            $value = str_replace(' ', ' ', trim($value));

            static::assertEquals($value, $line);
        }

        $this->spin(function () {
            $this->oroMainContext->pressKeyboardKey('Esc', 'WysiwygCodeTypeBlockEditor');
        }, .2);
    }

    /**
     * Example: I select "Text" component in canvas
     * Example: I select component in canvas by tree:
     *     | table | 1 |
     *     | table-row | 1 |
     *     | table-cell | 2 |
     * Will select second cell in first row in first table in the parent wrapper
     *
     * @When /^(?:|I )select "(?P<componentName>[^"]+)" component in canvas$/
     * @When /^(?:|I )select component in canvas by tree:$/
     *
     * @param string|TableNode $componentName
     */
    public function selectComponentInCanvas(string|TableNode $componentName): void
    {
        if (is_string($componentName)) {
            $this->getDriver()->switchToIFrame(0);
            $element = $this->createElement($componentName);
            $element->click();
            $this->getDriver()->switchToIFrame();
            return;
        }

        $layerTabBtn = $this->createElement('OpenLayerTab');
        if (!$layerTabBtn->hasClass('gjs-pn-active')) {
            $layerTabBtn->click();
        }

        $selectedComponent = $this->createElement('Layers');

        foreach ($componentName->getRows() as $row) {
            [$name, $eq] = $row;

            if (!$selectedComponent->hasClass('open')) {
                $selectedComponent->find('css', '[data-toggle-open]')->click();
            }

            $selectedComponents = $selectedComponent->findAll(
                'css',
                sprintf('.gjs-layer-children > .gjs-layers > .gjs-layer__t-%s', $name)
            );

            $selectedComponent = $selectedComponents[$eq - 1];
        }

        $selectedComponent->find('css', '[data-toggle-select]')->click();
    }

    /**
     * Example: I enter to edit mode "Text" component in canvas
     *
     * @When /^(?:|I )enter to edit mode "(?P<componentName>[^"]+)" component in canvas$/
     *
     * @param string $componentName
     */
    public function enterToEditModeComponentInCanvas(string $componentName): void
    {
        $this->getDriver()->switchToIFrame(0);

        $element = $this->createElement($componentName);
        $element->click();
        $element->doubleClick();

        $this->getDriver()->switchToIFrame();
    }

    /**
     * Example: I apply "bold" action in RTE
     *
     * @When /^(?:|I )apply "(?P<action>[^"]+)" action in RTE$/
     * @When /^(?:|I )apply "(?P<action>[^"]+)" action with "(?P<value>[^"]+)" in RTE$/
     *
     * @param string $action
     * @param string $value
     */
    public function applyActionInRte(string $action, string $value = ''): void
    {
        $page = $this->getSession()->getPage();
        $actionButton = $page->find(
            'css',
            sprintf(
                '.gjs-rte-toolbar [data-action-name="%s"], .gjs-rte-toolbar [data-command="%s"]',
                $action,
                $action
            )
        );

        $actionButton->click();

        if ($value) {
            $valueEl = $actionButton->find('css', 'select');
            $valueEl->selectOption($value);
        }
    }

    /**
     * Presses Enter|Space|ESC|ArrowUp|ArrowDown|ArrowLeft|ArrowRight key on specified element
     * Keys can be pressed with modifiers 'Shift', 'Alt', 'Ctrl' or 'Meta'
     *
     * Example: When I press "Shift+Enter" key on "Default Addresses" element in canvas
     * Example: And I press "Esc" key on "Default Addresses" element in canvas
     *
     * @When /^(?:|I )press "(?P<key>[^"]*)" key on "(?P<elementName>[\w\s]*)" element in canvas$/
     * @param string $key
     * @param string $elementName
     */
    public function pressKeyboardKeyInCanvas(string $key, string $elementName): void
    {
        $this->getDriver()->switchToIFrame(0);

        $this->oroMainContext->pressKeyboardKey($key, $elementName);

        $this->getDriver()->switchToIFrame();
    }

    /**
     * Example: When I clear canvas in WYSIWYG
     *
     * @When /^(?:|I )clear canvas in WYSIWYG$/
     */
    public function clearCanvasInWysiwyg(): void
    {
        $this->getSession()->wait(300);

        $button = $this->createElement('ClearCanvas');

        self::assertTrue($button->isValid(), 'WYSIWYG element "ClearCanvas" not found on page');

        $button->click();

        $this->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
     * Example: I make line break after "text" in current editing
     *
     * @When /^(?:|I )make line break after "(?P<strPos>[^"]+)" in current editing$/
     *
     * @param string $strPos
     */
    public function makeLineBreakInCurrentEditing(string $strPos): void
    {
        $this->selectTextRange($strPos, 'after');
        $this->pressKeyboardKeyInCanvas('Enter', 'ActiveEditableComponent');
    }
}
