<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\FluentRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Concerns\HasEmbeddedRules;
use Illuminate\Validation\Rules\Concerns\HasFieldModifiers;
use IteratorAggregate;
use Stringable;
use Traversable;

class Numeric implements FluentRule, IteratorAggregate, Stringable
{
    use Conditionable, HasEmbeddedRules, HasFieldModifiers, Macroable;

    /**
     * The string constraints for the number rule.
     */
    protected array $constraints = ['numeric'];

    /**
     * The field under validation must have a size between the given min and max (inclusive).
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return $this
     */
    public function between(int|float $min, int|float $max): static
    {
        return $this->addRule('between:'.$min.','.$max);
    }

    /**
     * The field under validation must contain the specified number of decimal places.
     *
     * @param  int  $min
     * @param  int|null  $max
     * @return $this
     */
    public function decimal(int $min, ?int $max = null): static
    {
        $rule = 'decimal:'.$min;

        if ($max !== null) {
            $rule .= ','.$max;
        }

        return $this->addRule($rule);
    }

    /**
     * The field under validation must have a different value than field.
     *
     * @param  string  $field
     * @return $this
     */
    public function different(string $field): static
    {
        return $this->addRule('different:'.$field);
    }

    /**
     * The integer under validation must have an exact number of digits.
     *
     * @param  int  $length
     * @return $this
     */
    public function digits(int $length): static
    {
        return $this->integer()->addRule('digits:'.$length);
    }

    /**
     * The integer under validation must between the given min and max number of digits.
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function digitsBetween(int $min, int $max): static
    {
        return $this->integer()->addRule('digits_between:'.$min.','.$max);
    }

    /**
     * The field under validation must be greater than the given field or value.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThan(string $field): static
    {
        return $this->addRule('gt:'.$field);
    }

    /**
     * The field under validation must be greater than or equal to the given field or value.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThanOrEqualTo(string $field): static
    {
        return $this->addRule('gte:'.$field);
    }

    /**
     * The field under validation must be an integer.
     *
     * @return $this
     */
    public function integer(bool $strict = false): static
    {
        return $this->addRule($strict ? 'integer:strict' : 'integer');
    }

    /**
     * The field under validation must be less than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThan(string $field): static
    {
        return $this->addRule('lt:'.$field);
    }

    /**
     * The field under validation must be less than or equal to the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThanOrEqualTo(string $field): static
    {
        return $this->addRule('lte:'.$field);
    }

    /**
     * The field under validation must be less than or equal to a maximum value.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function max(int|float $value): static
    {
        return $this->addRule('max:'.$value);
    }

    /**
     * The integer under validation must have a maximum number of digits.
     *
     * @param  int  $value
     * @return $this
     */
    public function maxDigits(int $value): static
    {
        return $this->addRule('max_digits:'.$value);
    }

    /**
     * The field under validation must have a minimum value.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function min(int|float $value): static
    {
        return $this->addRule('min:'.$value);
    }

    /**
     * The integer under validation must have a minimum number of digits.
     *
     * @param  int  $value
     * @return $this
     */
    public function minDigits(int $value): static
    {
        return $this->addRule('min_digits:'.$value);
    }

    /**
     * The field under validation must be a multiple of the given value.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function multipleOf(int|float $value): static
    {
        return $this->addRule('multiple_of:'.$value);
    }

    /**
     * The given field must match the field under validation.
     *
     * @param  string  $field
     * @return $this
     */
    public function same(string $field): static
    {
        return $this->addRule('same:'.$field);
    }

    /**
     * The field under validation must match the given value.
     *
     * @param  int  $value
     * @return $this
     */
    public function exactly(int $value): static
    {
        return $this->integer()->addRule('size:'.$value);
    }

    /**
     * The field under validation must match the value of the confirmation field.
     */
    public function confirmed(): static
    {
        return $this->addRule('confirmed');
    }

    /**
     * The field under validation must exist in another field's array.
     */
    public function inArray(string $field): static
    {
        return $this->addRule('in_array:'.$field);
    }

    /**
     * The field under validation must exist as a key in another field's array.
     */
    public function inArrayKeys(string $field): static
    {
        return $this->addRule('in_array_keys:'.$field);
    }

    /**
     * Each value in the field under validation must be unique.
     */
    public function distinct(?string $mode = null): static
    {
        return $this->addRule($mode ? 'distinct:'.$mode : 'distinct');
    }

    /**
     * Get an iterator for the validation rules.
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator([
            ...array_unique($this->constraints),
            ...$this->rules,
        ]);
    }

    /**
     * Convert the rule to a validation string.
     *
     * Lossy — only returns string constraints. Embedded rule objects
     * are NOT included. The parser uses getIterator() which returns
     * everything.
     */
    public function __toString(): string
    {
        if (! empty($this->rules)) {
            trigger_error(
                'Casting '.static::class.' to string discards embedded rule objects. Use the rule object directly instead of casting to string.',
                E_USER_DEPRECATED,
            );
        }

        return implode('|', array_unique($this->constraints));
    }
}
