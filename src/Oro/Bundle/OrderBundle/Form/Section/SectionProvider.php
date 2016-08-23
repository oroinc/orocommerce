<?php

namespace Oro\Bundle\OrderBundle\Form\Section;

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
        $formName = (string)$formName;
        if (!array_key_exists($formName, $this->sections)) {
            $this->sections[$formName] = [];
        }

        $this->sections[$formName] = array_merge($this->sections[$formName], $sections);
    }

    /**
     * @param string $formName
     * @return ArrayCollection
     */
    public function getSections($formName)
    {
        $formName = (string)$formName;
        if (!array_key_exists($formName, $this->sections)) {
            return new ArrayCollection();
        }

        $sections = new ArrayCollection($this->sections[$formName]);

        $criteria = Criteria::create();
        $criteria->orderBy(['order' => Criteria::ASC]);

        return $sections->matching($criteria);
    }
}
