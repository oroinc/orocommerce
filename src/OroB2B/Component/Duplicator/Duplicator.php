<?php

namespace OroB2B\Component\Duplicator;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;
use DeepCopy\Filter\Filter;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\Matcher;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use DeepCopy\Filter\ReplaceFilter;
use DeepCopy\Filter\ReplaceFilter as TypeReplaceFilter;
use DeepCopy\TypeFilter\ShallowCopyFilter;
use DeepCopy\TypeFilter\TypeFilter;
use DeepCopy\TypeMatcher\TypeMatcher;

class Duplicator
{
    /**
     * @param $object
     * @param array $settings
     * @return mixed
     */
    public function duplicate($object, array $settings = [])
    {
        $deepCopy = new DeepCopy();
        foreach ($settings as $option) {
            if (!isset($option[0]) || !isset($option[1])) {
                throw new \InvalidArgumentException('Invalid arguments to clone entity');
            }
            $filterOptions = $option[0];
            $matcherArguments = $option[1];
            $filter = $this->getFilter($filterOptions);
            if ($filter instanceof TypeFilter) {
                $matcher = new TypeMatcher($matcherArguments);
                $deepCopy->addTypeFilter($this->getTypeFilter($filterOptions), $matcher);
            } else {
                $deepCopy->addFilter($filter, $this->getMatcher($matcherArguments));
            }
        }

        return $deepCopy->copy($object);
    }

    /**
     * @param $filterOptions
     * @return Filter|TypeFilter
     * @internal param array|string $filterName
     */
    protected function getFilter($filterOptions)
    {
        $filterName = $filterOptions[0];
        $filterParameters = isset($filterOptions[1]) ? $filterOptions[1] : null;

        switch ($filterName) {
            case 'setNull':
                $filter = new SetNullFilter();
                break;
            case 'keep':
                $filter = new KeepFilter();
                break;
            case 'collection':
                $filter = new DoctrineCollectionFilter();
                break;
            case 'emptyCollection':
                $filter = new DoctrineEmptyCollectionFilter();
                break;
            case 'replace':
                $callBack = function () use ($filterParameters) {
                    return $filterParameters;
                };
                $filter = new ReplaceFilter($callBack);
                break;
            default:
                $filter = $this->getTypeFilter($filterOptions);
        }

        return $filter;
    }

    /**
     * @param $filterOptions
     * @return TypeFilter
     */
    protected function getTypeFilter($filterOptions)
    {
        $filterName = $filterOptions[0];
        $filterParameters = isset($filterOptions[1]) ? : null;

        switch ($filterName) {

            case 'shallowCopy':
                $filter = new ShallowCopyFilter();
                break;
            case 'typeReplace':
                $callBack = function () use ($filterParameters) {
                    return $filterParameters;
                };
                $filter = new TypeReplaceFilter($callBack);
                break;
            default:
                $message = 'Filter name %s not found in '
                    . '(setNull, keep, collection, emptyCollection, replace, typeReplace, shallowCopy)';
                $message = sprintf($message, $filterName);
                throw new \InvalidArgumentException($message);
        }

        return $filter;
    }

    /**
     * @param $matcherArguments
     * @return Matcher
     */
    protected function getMatcher($matcherArguments)
    {
        $matcherKeyword = $matcherArguments[0];
        $arguments = $matcherArguments[1];
        switch ($matcherKeyword) {
            case 'propertyName':
                $matcher = new PropertyNameMatcher($arguments);
                break;
            case 'property':
                $matcher = new PropertyMatcher($arguments[0], $arguments[1]);
                break;
            case 'propertyType':
                $matcher = new PropertyTypeMatcher($arguments);
                break;
            default:
                $message = 'Matcher name %s not found in (property, propertyName)';
                $message = sprintf($message, $matcherKeyword);
                throw new \InvalidArgumentException($message);
        }

        return $matcher;
    }
}
