<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\FluentRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Concerns\HasFieldModifiers;
use IteratorAggregate;
use Stringable;
use Traversable;

class BooleanRule implements FluentRule, IteratorAggregate, Stringable
{
    use Conditionable, HasFieldModifiers, Macroable;

    /**
     * The string constraints for the boolean rule.
     */
    protected array $constraints = ['boolean'];

    /**
     * The field under validation must be accepted ("yes", "on", 1, "1", true, "true").
     */
    public function accepted(): static
    {
        return $this->addRule('accepted');
    }

    /**
     * The field under validation must be accepted when the given condition is met.
     *
     * @param  string  $field
     * @param  mixed  ...$values
     */
    public function acceptedIf(string $field, mixed ...$values): static
    {
        return $this->addRule('accepted_if:'.$field.','.implode(',', $values));
    }

    /**
     * The field under validation must be declined ("no", "off", 0, "0", false, "false").
     */
    public function declined(): static
    {
        return $this->addRule('declined');
    }

    /**
     * The field under validation must be declined when the given condition is met.
     *
     * @param  string  $field
     * @param  mixed  ...$values
     */
    public function declinedIf(string $field, mixed ...$values): static
    {
        return $this->addRule('declined_if:'.$field.','.implode(',', $values));
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
        return implode('|', array_unique($this->constraints));
    }
}
