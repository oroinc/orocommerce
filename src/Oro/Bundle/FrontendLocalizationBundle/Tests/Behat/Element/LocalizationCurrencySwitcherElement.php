<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use WebDriver\Exception\ElementNotVisible;
use WebDriver\Exception\NoSuchElement;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LocalizationCurrencySwitcherElement extends Element
{
    public const TYPE_TOGGLE = 'toggle';
    public const TYPE_SELECT = 'select';
    public const TYPE_SINGLE = 'single';
    public const TYPE_TOGGLE_VERTICAL = 'toggle_vertical';
    public const LOCALIZATION_SWITCHER_ELEMENT = 'Localization Switcher';
    public const CURRENCY_SWITCHER_ELEMENT = 'Currency Switcher';

    public function getActiveLocalizationOption(): ?string
    {
        return $this->extractText($this->getElementByMappedSelector('ActiveLocalization')?->getHtml());
    }

    public function getActiveCurrencyOption(): ?string
    {
        return $this->extractText($this->getElementByMappedSelector('ActiveCurrency')?->getHtml());
    }

    public function getAvailableLocalizationOptions(): array
    {
        return $this->getAvailableElementOptions(self::LOCALIZATION_SWITCHER_ELEMENT);
    }

    public function getAvailableCurrencyOptions(): array
    {
        return $this->getAvailableElementOptions(self::CURRENCY_SWITCHER_ELEMENT);
    }

    public function setLocalizationValue(string $value): void
    {
        $this->setLocalizationOrCurrencyValue($value, self::LOCALIZATION_SWITCHER_ELEMENT);
    }

    public function setCurrencyValue(string $value): void
    {
        $this->setLocalizationOrCurrencyValue($value, self::CURRENCY_SWITCHER_ELEMENT);
    }

    public function getMainElementSelectorType(): string
    {
        if ($this->hasElementByMappedSelector('SelectMainElementContainer')) {
            return self::TYPE_SELECT;
        }

        if ($this->hasElementByMappedSelector('ToggleMainElementContainer')) {
            return self::TYPE_TOGGLE;
        }

        return self::TYPE_SINGLE;
    }

    public function getLocationElement(): ?string
    {
        if ($this->hasElementByMappedSelector('LocationAboveTheHeaderLocalizationSwitcher')
            || $this->hasElementByMappedSelector('LocationAboveTheHeaderCurrencySwitcher')
        ) {
            return 'above the header, separate switchers';
        }

        if ($this->hasElementByMappedSelector('LocationAboveTheHeaderSingleSwitcherButton')) {
            return 'above the header, as single switcher';
        }

        if ($this->hasElementByMappedSelector('SelectMainElementContainer')
            || $this->hasElementByMappedSelector('ToggleMainElementContainer')
        ) {
            return 'in the hamburger menu';
        }

        return null;
    }

    public function getInternalElementSelectorType($switcherElement): string
    {
        if ($this->hasInternalElementByMappedSelector('SelectInternalElementContainer', $switcherElement)) {
            return self::TYPE_SELECT;
        }

        if ($this->hasInternalElementByMappedSelector('ToggleInternalElementContainer', $switcherElement)) {
            return self::TYPE_TOGGLE;
        }

        if ($this->hasInternalElementByMappedSelector('ToggleVerticalInternalElementContainer', $switcherElement)) {
            return self::TYPE_TOGGLE_VERTICAL;
        }

        return self::TYPE_SINGLE;
    }

    protected function getAvailableElementOptions(string $elementName): array
    {
        $switcherElement = $this->getElement($elementName);

        return match ($this->getMainElementSelectorType()) {
            self::TYPE_SELECT => $this->getOptionsFromSelector($switcherElement),
            self::TYPE_TOGGLE => $this->getToggleOptions($switcherElement),
            default => []
        };
    }

    protected function setLocalizationOrCurrencyValue(string $value, string $elementName): void
    {
        $value = trim($value);
        $values = $this->getAvailableElementOptions($elementName);
        self::assertContains($value, $values, 'Unknown option');

        $mainSelectorType = $this->getMainElementSelectorType();
        self::assertContains($mainSelectorType, [self::TYPE_TOGGLE, self::TYPE_SELECT], 'Unsupported type');

        if ($mainSelectorType === self::TYPE_TOGGLE) {
            $this->setMainToggleValue($value);
        } elseif ($mainSelectorType === self::TYPE_SELECT) {
            $this->setMainSelectValue($value, $elementName);
        }
    }

    protected function setMainToggleValue(string $value): void
    {
        $valueElement = $this->getElementByMappedSelector('MainToggleLink', [$value]);
        $valueElement->focus();
        if ($valueElement->isVisible() && $valueElement->isValid()) {
            $valueElement->click();
        }
    }

    protected function setMainSelectValue(string $value, string $elementName): void
    {
        $switcherElement = $this->getElement($elementName);
        $internalSelectorType = $this->getInternalElementSelectorType($switcherElement);
        if ($internalSelectorType === self::TYPE_TOGGLE || $internalSelectorType === self::TYPE_TOGGLE_VERTICAL) {
            $this->setInternalToggleValue($value);
        } elseif ($internalSelectorType === self::TYPE_SELECT) {
            $this->setSelectValue($value, $switcherElement);
        }

        $saveButton = $this->getElementByMappedSelector('SaveButton');
        if ($saveButton && $saveButton->isValid()) {
            $saveButton->click();
        }
    }

    protected function setInternalToggleValue(string $value): void
    {
        $valueElement = $this->getElementByMappedSelector('InternalToggleLabel', [$value]);
        $valueElement->focus();
        if ($valueElement->isVisible() && $valueElement->isValid()) {
            $valueElement->click();
        }
    }

    protected function setSelectValue(string $value, Element $element): void
    {
        $select2 = $this->elementFactory->wrapElement('Select2Offscreen', $element);
        $select2->setValue($value);
    }

    protected function getOptionsFromSelector(Element $switcherElement): array
    {
        $footerExpand = $this->getElementByMappedSelector('FooterExpand');

        self::assertTrue($footerExpand->isValid());
        $this->spin(function () use ($footerExpand) {
            try {
                $footerExpand->click();
            } catch (NoSuchElement|ElementNotVisible $e) {
                return false;
            } finally {
                return $footerExpand->isVisible();
            }
        }, 60);

        return match ($this->getInternalElementSelectorType($switcherElement)) {
            self::TYPE_TOGGLE => $this->getToggleOptions($switcherElement),
            self::TYPE_SELECT => $this->getSelectOptions($switcherElement),
            self::TYPE_TOGGLE_VERTICAL => $this->getToggleVerticalOptions($switcherElement),
        };
    }

    protected function getToggleOptions(Element $switcherElement): array
    {
        $options = array_map(
            function (NodeElement $element) {
                $id = $element->getAttribute('id');
                $text = $this->find('xpath', "//label[@for='$id']")->getHtml();

                return $this->extractText($text);
            },
            $switcherElement->findAll('css', '[type="radio"]')
        );
        sort($options);

        return $options;
    }

    protected function getToggleVerticalOptions(Element $switcherElement): array
    {
        $options = array_map(
            function (NodeElement $element) {
                $id = $element->getAttribute('id');
                $text = $this->find('xpath', "//label[@for='$id']")->getText();

                return $this->extractText($text);
            },
            $switcherElement->findAll('css', '[type="radio"]')
        );
        sort($options);

        return $options;
    }

    protected function getSelectOptions(Element $switcherElement): array
    {
        $options = array_map(
            function (NodeElement $element) {
                $text = $element->getHtml();

                return $this->extractText($text);
            },
            $switcherElement->findAll('css', 'option')
        );
        sort($options);

        return $options;
    }

    private function extractText(?string $text): ?string
    {
        return $text ? trim(strip_tags($text)) : null;
    }

    protected function getElementByMappedSelector(string $selectorName, array $parameters = null)
    {
        [$type, $locator] = $this->getNormalizedSelector($selectorName, $parameters);

        return $this->find($type, $locator);
    }

    protected function hasElementByMappedSelector(string $selectorName, array $parameters = null): bool
    {
        [$type, $locator] = $this->getNormalizedSelector($selectorName, $parameters);

        return $this->has($type, $locator);
    }

    protected function hasInternalElementByMappedSelector(
        string $selectorName,
        Element $switcherElement,
        array $parameters = null
    ): bool {
        [$type, $locator] = $this->getNormalizedSelector($selectorName, $parameters);

        return $switcherElement->getParent()->getParent()->has($type, $locator);
    }

    protected function getNormalizedSelector(string $selectorName, ?array $parameters): array
    {
        $selector = $this->options['selectors'][$selectorName];
        if (is_string($selector)) {
            $locator = $selector;
            $type = 'css';
        } else {
            $locator = $selector['locator'];
            $type = $selector['type'];
        }

        if (str_contains($locator, '%') && $parameters) {
            $locator = sprintf($locator, ...$parameters);
        }

        return [$type, $locator];
    }
}
