<?php

namespace Illuminate\Validation\Rules\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\ExcludeIf;
use Illuminate\Validation\Rules\ExcludeUnless;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\ProhibitedUnless;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\RequiredUnless;

trait HasFieldModifiers
{
    /**
     * The embedded rule objects.
     */
    protected array $rules = [];

    /**
     * Add a rule to the validation rules.
     *
     * Strings are appended to $constraints. Objects are appended to $rules.
     * Both arrays are merged by getIterator().
     */
    protected function addRule(array|string|object $rules): static
    {
        if (is_object($rules)) {
            $this->rules[] = $rules;
        } else {
            $this->constraints = array_merge($this->constraints, Arr::wrap($rules));
        }

        return $this;
    }

    /**
     * Stop running validation rules for the field after the first failure.
     */
    public function bail(): static
    {
        return $this->addRule('bail');
    }

    /**
     * The field under validation may be null.
     */
    public function nullable(): static
    {
        return $this->addRule('nullable');
    }

    /**
     * The field under validation must be present in the input data and not empty.
     */
    public function required(): static
    {
        return $this->addRule('required');
    }

    /**
     * The field under validation will only be validated if it is present in the input data.
     */
    public function sometimes(): static
    {
        return $this->addRule('sometimes');
    }

    /**
     * The field under validation must not be empty when it is present.
     */
    public function filled(): static
    {
        return $this->addRule('filled');
    }

    /**
     * The field under validation must be present in the input data.
     */
    public function present(): static
    {
        return $this->addRule('present');
    }

    /**
     * The field under validation must not be present or must be empty.
     */
    public function prohibited(): static
    {
        return $this->addRule('prohibited');
    }

    /**
     * The field under validation must be entirely absent from the input data.
     */
    public function exclude(): static
    {
        return $this->addRule('exclude');
    }

    /**
     * The field under validation must not be present in the input data.
     */
    public function missing(): static
    {
        return $this->addRule('missing');
    }

    /**
     * The field is required when the condition is met.
     *
     * When given a Closure or bool, a RequiredIf rule object is embedded.
     * When given a string field name and values, a required_if string rule is added.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function requiredIf(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new RequiredIf($field));
        }

        return $this->addRule('required_if:'.$field.','.implode(',', $values));
    }

    /**
     * The field is required unless the condition is met.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function requiredUnless(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new RequiredUnless($field));
        }

        return $this->addRule('required_unless:'.$field.','.implode(',', $values));
    }

    /**
     * The field is required when any of the given fields are present.
     */
    public function requiredWith(string ...$fields): static
    {
        return $this->addRule('required_with:'.implode(',', $fields));
    }

    /**
     * The field is required when all of the given fields are present.
     */
    public function requiredWithAll(string ...$fields): static
    {
        return $this->addRule('required_with_all:'.implode(',', $fields));
    }

    /**
     * The field is required when any of the given fields are not present.
     */
    public function requiredWithout(string ...$fields): static
    {
        return $this->addRule('required_without:'.implode(',', $fields));
    }

    /**
     * The field is required when all of the given fields are not present.
     */
    public function requiredWithoutAll(string ...$fields): static
    {
        return $this->addRule('required_without_all:'.implode(',', $fields));
    }

    /**
     * The field is excluded when the condition is met.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function excludeIf(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new ExcludeIf($field));
        }

        return $this->addRule('exclude_if:'.$field.','.implode(',', $values));
    }

    /**
     * The field is excluded unless the condition is met.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function excludeUnless(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new ExcludeUnless($field));
        }

        return $this->addRule('exclude_unless:'.$field.','.implode(',', $values));
    }

    /**
     * The field is excluded when the given field is present.
     */
    public function excludeWith(string $field): static
    {
        return $this->addRule('exclude_with:'.$field);
    }

    /**
     * The field is excluded when the given field is not present.
     */
    public function excludeWithout(string $field): static
    {
        return $this->addRule('exclude_without:'.$field);
    }

    /**
     * The field is prohibited when the condition is met.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function prohibitedIf(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new ProhibitedIf($field));
        }

        return $this->addRule('prohibited_if:'.$field.','.implode(',', $values));
    }

    /**
     * The field is prohibited unless the condition is met.
     *
     * @param  \Closure|bool|string  $field
     * @param  mixed  ...$values
     */
    public function prohibitedUnless(Closure|bool|string $field, mixed ...$values): static
    {
        if ($field instanceof Closure || is_bool($field)) {
            return $this->addRule(new ProhibitedUnless($field));
        }

        return $this->addRule('prohibited_unless:'.$field.','.implode(',', $values));
    }

    /**
     * The field prohibits the given fields from being present.
     */
    public function prohibits(string ...$fields): static
    {
        return $this->addRule('prohibits:'.implode(',', $fields));
    }

    /**
     * Add a custom rule to the validation rules.
     *
     * Accepts a ValidationRule object, a Closure (fn ($attribute, $value, $fail) => ...),
     * or a string rule.
     *
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Closure(string, mixed, \Closure): void|string  $rule
     */
    public function rule(object|string $rule): static
    {
        return $this->addRule($rule);
    }
}
