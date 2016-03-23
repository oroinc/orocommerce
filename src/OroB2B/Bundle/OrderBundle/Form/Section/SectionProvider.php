<?php

namespace OroB2B\Bundle\OrderBundle\Form\Section;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class SectionProvider
{
    /**
     * @var array
     */
    protected $sections = [];

    /**
     * @param string $formName
     * @param array $sections
     */
    public function addSections($formName, array $sections)
    {
        if (!array_key_exists((string)$formName, $this->sections)) {
            $this->sections[(string)$formName] = [];
        }

        $this->sections[(string)$formName] = array_merge($this->sections[(string)$formName], $sections);
    }

    /**
     * @param string $formName
     * @return ArrayCollection
     */
    public function getSections($formName)
    {
        $sections = new ArrayCollection($this->sections[(string)$formName]);

        $criteria = Criteria::create();
        $criteria->orderBy(['order' => Criteria::ASC]);

        return $sections->matching($criteria);
    }
}
