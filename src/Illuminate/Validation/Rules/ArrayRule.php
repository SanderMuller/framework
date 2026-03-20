<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\FluentRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Concerns\HasFieldModifiers;
use IteratorAggregate;
use Stringable;
use Traversable;

use function Illuminate\Support\enum_value;

class ArrayRule implements FluentRule, IteratorAggregate, Stringable
{
    use Conditionable, HasFieldModifiers, Macroable;

    /**
     * The accepted keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * The string constraints for the array rule.
     */
    protected array $constraints = [];

    /**
     * Create a new array rule instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|null  $keys
     */
    public function __construct($keys = null)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    /**
     * The array must have at least the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function min(int $value): static
    {
        return $this->addRule('min:'.$value);
    }

    /**
     * The array must not have more than the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function max(int $value): static
    {
        return $this->addRule('max:'.$value);
    }

    /**
     * The array must have between the given min and max number of items.
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function between(int $min, int $max): static
    {
        return $this->addRule('between:'.$min.','.$max);
    }

    /**
     * The array must have exactly the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function exactly(int $value): static
    {
        return $this->addRule('size:'.$value);
    }

    /**
     * The array must be a sequential list (non-associative).
     *
     * @return $this
     */
    public function list(): static
    {
        return $this->addRule('list');
    }

    /**
     * The array must contain the given keys.
     *
     * @param  string  ...$keys
     * @return $this
     */
    public function requiredArrayKeys(string ...$keys): static
    {
        return $this->addRule('required_array_keys:'.implode(',', $keys));
    }

    /**
     * Get an iterator for the validation rules.
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator([
            $this->buildArrayRule(),
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
     *
     * @return string
     */
    public function __toString()
    {
        if (! empty($this->rules)) {
            trigger_error(
                'Casting '.static::class.' to string discards embedded rule objects. Use the rule object directly instead of casting to string.',
                E_USER_DEPRECATED,
            );
        }

        return implode('|', array_filter([
            $this->buildArrayRule(),
            ...array_unique($this->constraints),
        ]));
    }

    /**
     * Build the base array rule string.
     */
    protected function buildArrayRule(): string
    {
        if (empty($this->keys)) {
            return 'array';
        }

        $keys = array_map(
            static fn ($key) => enum_value($key),
            $this->keys,
        );

        return 'array:'.implode(',', $keys);
    }
}
