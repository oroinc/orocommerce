<?php

namespace OroB2B\Bundle\AccountBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class VisibilityChoicesProvider
{
    /**
     * @var TranslatorInterface
     */
    public $translator;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @param TranslatorInterface $translator
     * @param Registry $registry
     */
    public function __construct(TranslatorInterface $translator, Registry $registry)
    {
        $this->translator = $translator;
        $this->registry = $registry;
    }

    /**
     * @param string $sourceClass
     * @param object $target
     * @return array
     */
    public function getFormattedChoices($sourceClass, $target)
    {
        $choices = $this->getChoices($sourceClass, $target);

        $sourceClassReflection = new \ReflectionClass($sourceClass);
        $className = strtolower($sourceClassReflection->getShortName());
        $translationPattern = 'orob2b.account.visibility.' . $className . '.choice.%s';

        return $this->formatChoices($translationPattern, $choices);
    }

    /**
     * @param string $sourceClass
     * @param object $target
     * @return array
     */
    public function getChoices($sourceClass, $target)
    {
        $choices = call_user_func([$sourceClass, 'getVisibilityList'], $target);

        if ($target instanceof Product && !$this->getProductCategory($target)) {
            unset($choices[array_search(constant($sourceClass.'::CATEGORY'), $choices)]);
            $choices = array_values($choices);
        }

        return $choices;
    }

    /**
     * @param Product $product
     * @return null|\OroB2B\Bundle\CatalogBundle\Entity\Category
     */
    protected function getProductCategory(Product $product)
    {
        return $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
    }

    /**
     * @param string $translationPattern
     * @param array $choices
     * @return array
     */
    public function formatChoices($translationPattern, $choices)
    {
        $result = [];
        foreach ($choices as $choice) {
            $result[$choice] = $this->format($translationPattern, $choice);
        }

        return $result;
    }

    /**
     * @param string $translationPattern
     * @param string $choice
     * @return array
     */
    public function format($translationPattern, $choice)
    {
        return $this->translator->trans(sprintf($translationPattern, $choice));
    }
}
