<?php

namespace Illuminate\Validation\Concerns;

use Illuminate\Support\Str;

trait AccessesSiblingData
{
    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the fully qualified attribute name for a sibling of the given attribute.
     *
     * @param  string  $attribute
     * @param  string  $sibling
     * @return string
     */
    protected function siblingAttribute(string $attribute, string $sibling): string
    {
        if (! str_contains($attribute, '.')) {
            return $sibling;
        }

        return Str::beforeLast($attribute, '.').'.'.$sibling;
    }

    /**
     * Get the value of a sibling attribute.
     *
     * @param  string  $attribute
     * @param  string  $sibling
     * @return mixed
     */
    protected function siblingValue(string $attribute, string $sibling): mixed
    {
        return data_get($this->data, $this->siblingAttribute($attribute, $sibling));
    }
}
