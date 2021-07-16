<?php

namespace Oro\Bundle\OrderBundle\Form\Section;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\FormRegistryInterface;

class SectionProvider
{
    /**
     * @var array
     */
    protected $sections = [];

    /**
     * @var FormRegistryInterface
     */
    private $formRegistry;

    public function __construct(FormRegistryInterface $formRegistry)
    {
        $this->formRegistry = $formRegistry;
    }

    /**
     * @param string $formTypeClass
     * @param array $sections
     */
    public function addSections($formTypeClass, array $sections)
    {
        $formName = $this->getSectionNameByTypeClass($formTypeClass);
        if (!array_key_exists($formName, $this->sections)) {
            $this->sections[$formName] = [];
        }

        $this->sections[$formName] = array_merge($this->sections[$formName], $sections);
    }

    /**
     * @param string $formTypeClass
     * @return ArrayCollection
     */
    public function getSections($formTypeClass)
    {
        $formName = $this->getSectionNameByTypeClass($formTypeClass);
        if (!array_key_exists($formName, $this->sections)) {
            return new ArrayCollection();
        }

        $sections = new ArrayCollection($this->sections[$formName]);

        $criteria = Criteria::create();
        $criteria->orderBy(['order' => Criteria::ASC]);

        return $sections->matching($criteria);
    }

    /**
     * @param string $formTypeClass
     * @return string
     */
    private function getSectionNameByTypeClass($formTypeClass)
    {
        $formType = $this->formRegistry->getType($formTypeClass);

        return $formType->getBlockPrefix();
    }
}
