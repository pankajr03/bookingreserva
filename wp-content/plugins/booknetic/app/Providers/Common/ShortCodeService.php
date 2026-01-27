<?php

namespace BookneticApp\Providers\Common;

class ShortCodeService
{
    private $shortCodeCategories = [];

    private $replacers = [];

    private $lazyShortCodeCallbacks = [];

    public function addReplacer($replacer)
    {
        if (is_callable($replacer)) {
            $this->replacers[] = $replacer;
        }

        return $this;
    }

    public function registerShortCodesLazily($callback): self
    {
        if (is_callable($callback)) {
            $this->lazyShortCodeCallbacks[] = $callback;
        }

        return $this;
    }

    /**
     * @param array $data
     */
    public function replace($text, $data)
    {
        $text = apply_filters('bkntc_short_code_before_replace', $text, $data);

        foreach ($this->replacers as $replacer) {
            $text = $replacer($text, $data, $this);
        }

        return apply_filters('bkntc_short_code_after_replace', $text, $data);
    }

    public function getShortCodesList($filterByDependsParameter = [], $filterByKind = [], $groupByCategory = false)
    {
        foreach ($this->lazyShortCodeCallbacks as $callback) {
            $callback($this);
        }
        $this->lazyShortCodeCallbacks = [];

        $filteredShortCodesList = [];
        foreach ($this->shortCodeCategories as $category => $shortCodeCategoryInfo) {
            $categoryName   = $shortCodeCategoryInfo['name'];
            $shortCodesList = $shortCodeCategoryInfo['short_codes'];

            foreach ($shortCodesList as $shortCodeInf) {
                if (
                    (empty($filterByDependsParameter) || empty($shortCodeInf['depends']) || in_array($shortCodeInf['depends'], $filterByDependsParameter)) &&
                    (empty($filterByKind) || in_array($shortCodeInf['kind'], $filterByKind))
                ) {
                    if ($groupByCategory) {
                        if (! isset($filteredShortCodesList[ $category ])) {
                            $filteredShortCodesList[ $category ] = [
                                'name'          =>  $categoryName,
                                'short_codes'   =>  []
                            ];
                        }

                        $filteredShortCodesList[ $category ]['short_codes'][] = $shortCodeInf;
                    } else {
                        $filteredShortCodesList[] = $shortCodeInf;
                    }
                }
            }
        }

        return $filteredShortCodesList;
    }

    public function registerCategory($shortCodeCategory, $name)
    {
        if (! isset($this->shortCodeCategories[ $shortCodeCategory ])) {
            $this->shortCodeCategories[ $shortCodeCategory ] = [ 'short_codes' => [] ];
        }

        $this->shortCodeCategories[ $shortCodeCategory ]['name'] = $name;
    }

    public function registerShortCode($shortCode, $params = [])
    {
        $defaultParams = [
            'name'          =>  '',
            'description'   =>  '',
            'category'      =>  'others',
            'depends'       =>  '',
            'kind'          =>  ''
        ];
        $params['code'] = $shortCode;
        $params = array_merge($defaultParams, $params);
        $shortCodeCategory = $params['category'];

        if (! isset($this->shortCodeCategories[ $shortCodeCategory ])) {
            $this->registerCategory($shortCodeCategory, $shortCodeCategory);
        }

        $this->shortCodeCategories[ $shortCodeCategory ]['short_codes'][] = $params;
    }
}
