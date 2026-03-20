# Fluent Validation Rule Builders — Spec

## Goal

Make the fluent validation API comprehensive enough that most validation rules can be expressed entirely within the fluent chain, eliminating the need to mix array syntax with builder objects.

Each builder is self-contained and focused on its type. No cross-type composition — use the right entry point for your field type.

```php
// Before
'name'  => ['required', 'string', 'min:2', 'max:255'],
'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
'age'   => ['nullable', 'numeric', 'integer', 'min:0'],
'terms' => ['required', 'boolean', 'accepted'],

// After
'name'  => Rule::string()->required()->min(2)->max(255),
'email' => Rule::email()->required()->rfcCompliant()->max(255)->unique('users', 'email'),
'age'   => Rule::numeric()->nullable()->integer()->min(0),
'terms' => Rule::boolean()->required()->accepted(),
```

---

## Architecture Changes

### 1. `Concerns\HasFieldModifiers` trait

Shared across FluentRule builders (`StringRule`, `Numeric`, `Date`, `ArrayRule`, `BooleanRule`). Contains field-level modifiers that are not type-specific.

**Requires:** the consuming class must define `addRule(array|string|object $rules): static`. Each class implements this differently:

- **FluentRule builders** (`StringRule`, `Numeric`, `Date`, `ArrayRule`, `BooleanRule`): routes strings to `$constraints`, objects to `$rules`.
- **Rule-interface builders** (`File`, `ImageFile`, `Email`, `Password`): routes to a `$modifiers` array that gets prepended in `getIterator()`.

This means all builders use the trait — no duplication of modifier methods.

```php
trait HasFieldModifiers
{
    abstract protected function addRule(array|string|object $rules): static;

    // Core modifiers
    public function bail(): static { return $this->addRule('bail'); }
    public function nullable(): static { return $this->addRule('nullable'); }
    public function required(): static { return $this->addRule('required'); }
    public function sometimes(): static { return $this->addRule('sometimes'); }
    public function filled(): static { return $this->addRule('filled'); }
    public function present(): static { return $this->addRule('present'); }
    public function prohibited(): static { return $this->addRule('prohibited'); }
    public function exclude(): static { return $this->addRule('exclude'); }
    public function missing(): static { return $this->addRule('missing'); }

    // Conditional required
    // Accepts either (string $field, mixed ...$values) for string rules
    // or (Closure|bool $callback) to embed a RequiredIf/RequiredUnless object
    public function requiredIf(Closure|bool|string $field, mixed ...$values): static { ... }
    public function requiredUnless(Closure|bool|string $field, mixed ...$values): static { ... }
    public function requiredWith(string ...$fields): static { ... }
    public function requiredWithAll(string ...$fields): static { ... }
    public function requiredWithout(string ...$fields): static { ... }
    public function requiredWithoutAll(string ...$fields): static { ... }

    // Conditional exclude
    // Same overloaded signature: (Closure|bool) or (string $field, mixed ...$values)
    public function excludeIf(Closure|bool|string $field, mixed ...$values): static { ... }
    public function excludeUnless(Closure|bool|string $field, mixed ...$values): static { ... }
    public function excludeWith(string $field): static { ... }
    public function excludeWithout(string $field): static { ... }

    // Conditional prohibited
    public function prohibitedIf(Closure|bool|string $field, mixed ...$values): static { ... }
    public function prohibitedUnless(Closure|bool|string $field, mixed ...$values): static { ... }
    public function prohibits(string ...$fields): static { ... }

    // Remaining conditional modifiers omitted for brevity — all follow the same
    // one-liner pattern. The less common ones (requiredIfAccepted, requiredIfDeclined,
    // prohibitedIfAccepted, prohibitedIfDeclined, presentIf, presentUnless, presentWith,
    // presentWithAll, missingIf, missingUnless, missingWith, missingWithAll) are still
    // available via the escape hatch: ->rule('present_with_all:foo,bar')

    // Escape hatch — available on every builder
    public function rule(object|Closure|string $rule): static { return $this->addRule($rule); }
}
```

**All builders use this trait.** Each implements `addRule()` with its own storage:
- FluentRule builders: routes to `$constraints` (strings) and `$rules` (objects)
- File/ImageFile/Email/Password: routes to `$modifiers` array, prepended in `getIterator()`

### 2. Marker interface: `FluentRule`

A new empty marker interface that all Stringable fluent builders implement. This keeps the parser explicit about which classes it handles.

```php
namespace Illuminate\Contracts\Validation;

interface FluentRule
{
    //
}
```

**Implementors:** `StringRule`, `Numeric`, `Date`, `ArrayRule`, `BooleanRule`

### 3. File/ImageFile/Email/Password: `$modifiers` array + `IteratorAggregate`

These classes use the `Rule` interface with `passes()`/`message()`, not the `FluentRule` marker. They use the `HasFieldModifiers` trait but implement `addRule()` to store in a `$modifiers` array instead of `$constraints`:

```php
// Shared pattern for File, ImageFile, Email, Password:
protected array $modifiers = [];

protected function addRule(array|string|object $rules): static
{
    if (is_object($rules)) {
        $this->modifiers[] = $rules;
    } else {
        $this->modifiers = array_merge($this->modifiers, Arr::wrap($rules));
    }

    return $this;
}
```

`getIterator()` prepends `$modifiers` before the class's own rules:

```php
// Password example:
public function getIterator(): Traversable
{
    return new ArrayIterator([
        ...$this->modifiers,          // bail, required, nullable, etc.
        'string',
        'min:'.$this->min,
        ...($this->max ? ['max:'.$this->max] : []),
        ...$this->customRules,
    ]);
}
```

Password's existing static factories (`Password::required()`, `Password::sometimes()`) are refactored to delegate to the instance method:

```php
public static function required(): static
{
    return static::default()->required();  // calls HasFieldModifiers::required()
}
```

The `$this->required` and `$this->sometimes` boolean properties are replaced by the `$modifiers` array. This is a minor internal refactor — the public API is unchanged.

**Note:** `Password::required()` already calls `static::default()` internally, so the refactor to `static::default()->required()` preserves identical semantics — including inheriting global defaults set via `Password::defaults(fn () => ...)`.

---

## StringRule — Full API

### Existing methods (unchanged)
```php
Rule::string()->alpha(?bool $ascii)
Rule::string()->alphaDash(?bool $ascii)
Rule::string()->alphaNumeric(?bool $ascii)
Rule::string()->ascii()
Rule::string()->between(int $min, int $max)
Rule::string()->doesntEndWith(string ...$values)
Rule::string()->doesntStartWith(string ...$values)
Rule::string()->endsWith(string ...$values)
Rule::string()->exactly(int $value)
Rule::string()->lowercase()
Rule::string()->max(int $value)
Rule::string()->min(int $value)
Rule::string()->startsWith(string ...$values)
Rule::string()->uppercase()
```

### Field modifiers (from trait)
```php
Rule::string()->bail()
Rule::string()->nullable()
Rule::string()->required()
Rule::string()->sometimes()
Rule::string()->filled()
Rule::string()->present()
Rule::string()->prohibited()
Rule::string()->requiredIf('role', 'admin')
Rule::string()->requiredIf(fn () => $user->isAdmin())  // closure form
Rule::string()->requiredWith('first_name')
// ... all conditional modifiers from trait
```

### Format validators (new — string rules)
```php
Rule::string()->url()                            // string|url
Rule::string()->activeUrl()                      // string|active_url
Rule::string()->uuid()                           // string|uuid
Rule::string()->ulid()                           // string|ulid
Rule::string()->json()                           // string|json
Rule::string()->ip()                             // string|ip
Rule::string()->ipv4()                           // string|ipv4
Rule::string()->ipv6()                           // string|ipv6
Rule::string()->macAddress()                     // string|mac_address
Rule::string()->regex(string $pattern)           // string|regex:/pattern/
Rule::string()->notRegex(string $pattern)        // string|not_regex:/pattern/
Rule::string()->timezone()                       // string|timezone
Rule::string()->hexColor()                       // string|hex_color
Rule::string()->date()                           // string|date (validates parseable date)
Rule::string()->dateFormat(string $format)        // string|date_format:Y-m-d
```

### Equality / comparison (new)
```php
Rule::string()->in(array $values)                // embeds In rule object
Rule::string()->notIn(array $values)             // embeds NotIn rule object
Rule::string()->confirmed()                      // string|confirmed
Rule::string()->currentPassword(?string $guard)   // string|current_password or string|current_password:api
Rule::string()->same(string $field)              // string|same:field
Rule::string()->different(string $field)          // string|different:field
Rule::string()->inArray(string $field)            // string|in_array:field.*
Rule::string()->inArrayKeys(string $field)        // string|in_array_keys:field
Rule::string()->distinct(?string $mode)            // string|distinct or string|distinct:strict
```

### Escape hatch
```php
Rule::string()->rule(new MyCustomRule)            // embeds any ValidationRule object
Rule::string()->rule(fn (string $attribute, mixed $value, Closure $fail) => ...)
```

### Usage examples
```php
// User registration
'name'     => Rule::string()->required()->min(2)->max(255),

// Profile update
'bio'      => Rule::string()->nullable()->max(1000),
'website'  => Rule::string()->nullable()->url()->max(255),
'timezone' => Rule::string()->sometimes()->timezone(),

// API input
'callback_url'   => Rule::string()->requiredIf('notify', true)->activeUrl(),
'format'         => Rule::string()->required()->in(['json', 'xml', 'csv']),
'correlation_id' => Rule::string()->nullable()->uuid(),

// Password change
'current_password' => Rule::string()->required()->currentPassword(),

// Custom validation
'slug' => Rule::string()->required()->regex('/^[a-z0-9-]+$/')->rule(new UniqueSlugRule($post)),
```

---

## Numeric — Full API

### Existing methods (unchanged)
```php
Rule::numeric()->between(int|float $min, int|float $max)
Rule::numeric()->decimal(int $min, ?int $max)
Rule::numeric()->different(string $field)
Rule::numeric()->digits(int $length)
Rule::numeric()->digitsBetween(int $min, int $max)
Rule::numeric()->greaterThan(string $field)
Rule::numeric()->greaterThanOrEqualTo(string $field)
Rule::numeric()->integer(?bool $strict)
Rule::numeric()->lessThan(string $field)
Rule::numeric()->lessThanOrEqualTo(string $field)
Rule::numeric()->max(int|float $value)
Rule::numeric()->maxDigits(int $value)
Rule::numeric()->min(int|float $value)
Rule::numeric()->minDigits(int $value)
Rule::numeric()->multipleOf(int|float $value)
Rule::numeric()->same(string $field)
Rule::numeric()->exactly(int $value)
```

### Field modifiers (from trait)
```php
Rule::numeric()->bail()
Rule::numeric()->nullable()
Rule::numeric()->required()
Rule::numeric()->sometimes()
// ... all conditional modifiers from trait
```

### New methods
```php
Rule::numeric()->in(array $values)               // embeds In rule object
Rule::numeric()->notIn(array $values)             // embeds NotIn rule object
Rule::numeric()->confirmed()                      // numeric|confirmed
Rule::numeric()->inArray(string $field)            // numeric|in_array:field.*
Rule::numeric()->inArrayKeys(string $field)        // numeric|in_array_keys:field

// Enum
Rule::numeric()->enum(Priority::class)            // embeds Enum object
Rule::numeric()->enum(Priority::class, fn (Enum $e) => $e->except([Priority::Deprecated]))

// Database
Rule::numeric()->unique(string $table, ?string $column = null)
Rule::numeric()->exists(string $table, ?string $column = null)

// Escape hatch
Rule::numeric()->rule(new MyCustomRule)
Rule::numeric()->rule(fn (string $attribute, mixed $value, Closure $fail) => ...)
```

### Usage examples
```php
'price'       => Rule::numeric()->required()->decimal(2)->min(0)->max(999999.99),
'quantity'    => Rule::numeric()->required()->integer()->min(1)->max(100),
'rating'      => Rule::numeric()->nullable()->between(1, 5)->decimal(1),
'tax_rate'    => Rule::numeric()->requiredIf('taxable', true)->decimal(2, 4)->between(0, 100),
'category_id' => Rule::numeric()->required()->integer()->exists('categories', 'id'),
'priority'    => Rule::numeric()->required()->enum(Priority::class),
```

---

## Date — Full API

### Existing methods (unchanged)
```php
Rule::date()->format(string $format)
Rule::date()->before(DateTimeInterface|string $date)
Rule::date()->after(DateTimeInterface|string $date)
Rule::date()->beforeOrEqual(DateTimeInterface|string $date)
Rule::date()->afterOrEqual(DateTimeInterface|string $date)
Rule::date()->between(DateTimeInterface|string $from, DateTimeInterface|string $to)
Rule::date()->betweenOrEqual(DateTimeInterface|string $from, DateTimeInterface|string $to)
Rule::date()->beforeToday() / afterToday() / todayOrBefore() / todayOrAfter()
Rule::date()->past() / future() / nowOrPast() / nowOrFuture()
```

### Field modifiers (from trait)
```php
Rule::date()->bail()
Rule::date()->nullable()
Rule::date()->required()
Rule::date()->sometimes()
// ... all conditional modifiers from trait
```

### New methods
```php
Rule::date()->same(string $field)
Rule::date()->different(string $field)
Rule::date()->dateEquals(string $date)            // date|date_equals:2024-01-01

// Database
Rule::date()->unique(string $table, ?string $column = null)

// Escape hatch
Rule::date()->rule(new MyCustomRule)
```

### Usage examples
```php
'start_date' => Rule::date()->required()->afterToday(),
'end_date'   => Rule::date()->required()->after('start_date'),
'birthday'   => Rule::date()->nullable()->before('today'),
'expires_at' => Rule::date()->requiredIf('type', 'trial')->future(),
```

---

## ArrayRule — Full API (enhanced)

Currently ArrayRule only supports `Rule::array(['key1', 'key2'])`. Enhance to a full constraint builder.

### New architecture
```php
class ArrayRule implements Stringable, FluentRule, IteratorAggregate
{
    use Conditionable, Macroable, HasFieldModifiers;

    protected ?array $keys = null;
    protected array $constraints = ['array'];

    // ... addRule(), __toString() like StringRule/Numeric
}
```

### Methods
```php
// Existing
Rule::array()                                     // array
Rule::array(['name', 'email'])                    // array:name,email

// Field modifiers (from trait)
Rule::array()->required()
Rule::array()->nullable()
Rule::array()->sometimes()
Rule::array()->bail()
// ... all conditional modifiers

// Size constraints (new)
Rule::array()->min(int $value)                    // array|min:1
Rule::array()->max(int $value)                    // array|max:10
Rule::array()->between(int $min, int $max)        // array|between:1,10
Rule::array()->exactly(int $value)                // array|size:5

// Content rules (new — wraps existing Contains/DoesntContain rule classes)
Rule::array()->contains(mixed ...$values)         // embeds Contains rule object
Rule::array()->doesntContain(mixed ...$values)    // embeds DoesntContain rule object
Rule::array()->list()                             // array|list (must be sequential, non-associative)
Rule::array()->requiredArrayKeys(string ...$keys) // array|required_array_keys:key1,key2
Rule::array()->inArray(string $field)             // array|in_array:field.*

// Escape hatch
Rule::array()->rule(new MyCustomRule)
```

### Usage examples
```php
'tags'     => Rule::array()->required()->min(1)->max(10),
'roles'    => Rule::array(['admin', 'editor', 'viewer'])->required(),
'settings' => Rule::array()->nullable()->max(50),
'items'    => Rule::array()->requiredWith('order_id')->between(1, 100),
'ids'      => Rule::array()->required()->list()->min(1),
```

---

## BooleanRule — Full API (new)

New builder for boolean validation. Accepts `true`, `false`, `1`, `0`, `"1"`, `"0"`.

### Architecture
```php
class BooleanRule implements Stringable, FluentRule, IteratorAggregate
{
    use Conditionable, Macroable, HasFieldModifiers;

    protected array $constraints = ['boolean'];

    // ... addRule(), __toString() like StringRule/Numeric
}
```

### Factory method
```php
// Rule.php
public static function boolean(): BooleanRule
{
    return new BooleanRule;
}
```

### Methods
```php
Rule::boolean()->required()
Rule::boolean()->nullable()
Rule::boolean()->sometimes()
Rule::boolean()->bail()
// ... all conditional modifiers from trait

Rule::boolean()->accepted()                       // boolean|accepted
Rule::boolean()->acceptedIf(string $field, mixed ...$values)
Rule::boolean()->declined()                       // boolean|declined
Rule::boolean()->declinedIf(string $field, mixed ...$values)
```

**Note:** verify that `boolean|accepted` composes correctly. `accepted` already implies boolean-like values (`'yes'`, `'on'`, `1`, `true`), but `boolean` is stricter (`true`, `false`, `1`, `0`, `"1"`, `"0"`). The intersection may reject valid `accepted` values like `'yes'` and `'on'`. If so, `accepted()` should be usable without the `boolean` base — consider whether `Rule::boolean()->accepted()` should drop `boolean` from the output, or document the interaction clearly. Needs a test.

### Usage examples
```php
'is_active'  => Rule::boolean()->required(),
'terms'      => Rule::boolean()->accepted(),
'opt_out'    => Rule::boolean()->nullable(),
'notify'     => Rule::boolean()->requiredIf('channel', 'email'),
'gdpr'       => Rule::boolean()->acceptedIf('country', 'DE'),
```

---

## File / ImageFile — Full API (enhanced)

Adopt Password's `IteratorAggregate` pattern to support field modifiers.

### Methods
```php
// Field modifiers
Rule::file()->required()
Rule::file()->nullable()
Rule::file()->sometimes()
Rule::file()->bail()
// ... all conditional modifiers

// Existing File methods (unchanged)
Rule::file()->extensions(string|array $ext)
Rule::file()->size(string|int $size)
Rule::file()->between(string|int $min, string|int $max)
Rule::file()->min(string|int $size)
Rule::file()->max(string|int $size)
Rule::file()->encoding(string $encoding)
Rule::file()->rules(string|array $rules)

// New File methods
// NOTE: mimes() validates by file EXTENSION (despite the name) — maps extension
// to expected MIME type. mimetypes() validates the actual MIME type from file content.
// This naming inconsistency is pre-existing in Laravel. Docblocks must be explicit.
Rule::file()->mimes(string ...$mimes)             // by extension: mimes('pdf', 'doc', 'xlsx')
Rule::file()->mimetypes(string ...$types)          // by MIME type: mimetypes('application/pdf', 'image/png')

// ImageFile-specific (unchanged)
Rule::imageFile()->dimensions(Dimensions $dimensions)

// New ImageFile convenience — dimensions without separate Rule::dimensions() call
Rule::imageFile()->width(int $value)               // exact width
Rule::imageFile()->height(int $value)              // exact height
Rule::imageFile()->minWidth(int $value)
Rule::imageFile()->minHeight(int $value)
Rule::imageFile()->maxWidth(int $value)
Rule::imageFile()->maxHeight(int $value)
Rule::imageFile()->ratio(float|string $value)     // accepts 16/9 (float) or '16/9' (string)
Rule::imageFile()->minRatio(float|string $value)
Rule::imageFile()->maxRatio(float|string $value)
Rule::imageFile()->ratioBetween(float|string $min, float|string $max)

// Convenience methods build a Dimensions object internally. If both convenience
// methods and ->dimensions() are called, the last Dimensions object wins.
// Don't mix: use either convenience methods OR ->dimensions(), not both.
```

### Usage examples
```php
'avatar' => Rule::imageFile()->nullable()->max('2mb')->maxWidth(500)->maxHeight(500),
'resume' => Rule::file()->required()->mimes('pdf', 'doc', 'docx')->max('10mb'),
'import' => Rule::file()->requiredIf('action', 'import')->extensions('csv')->encoding('utf-8'),
'logo'   => Rule::imageFile(allowSvg: true)->nullable()->max('1mb'),
'photo'  => Rule::imageFile()->required()->mimetypes('image/jpeg', 'image/png')->maxWidth(1920)->ratio(16/9),
'banner' => Rule::imageFile()->nullable()->max('5mb')->minWidth(800)->ratioBetween(2.0, 3.0),
```

---

## Email — Full API (enhanced)

Add field modifiers via `HasFieldModifiers` trait and database rules via `HasEmbeddedRules` trait, using the `$modifiers` + `IteratorAggregate` pattern. Email is a self-contained builder — no need to go through `Rule::string()->email()`.

### Methods
```php
// Field modifiers
Rule::email()->required()
Rule::email()->nullable()
Rule::email()->sometimes()
Rule::email()->bail()
// ... all conditional modifiers

// Existing Email methods (unchanged)
Rule::email()->rfcCompliant(?bool $strict)
Rule::email()->strict()
Rule::email()->validateMxRecord()
Rule::email()->preventSpoofing()
Rule::email()->withNativeValidation(?bool $allowUnicode)
Rule::email()->rules(string|array $rules)

// From HasEmbeddedRules trait
Rule::email()->unique(string $table, ?string $column = null)
Rule::email()->exists(string $table, ?string $column = null)

// New convenience method — max() is the only string constraint added to Email
// directly, because it's the single most common companion rule. All other
// string constraints should use the escape hatch: ->rule('min:5')
Rule::email()->max(int $value)                    // common: email with max length
```

### Usage examples
```php
'email'         => Rule::email()->required()->rfcCompliant()->max(255)->unique('users', 'email'),
'contact_email' => Rule::email()->nullable()->preventSpoofing(),
'work_email'    => Rule::email()->requiredIf('type', 'business')->validateMxRecord(),
```

---

## Password — Full API (already exists, minor additions)

Password already has `required()`, `sometimes()`, and `IteratorAggregate`. Its `$this->required` and `$this->sometimes` boolean properties are refactored to use the shared `$modifiers` array pattern (see Architecture section 3), allowing it to use the `HasFieldModifiers` trait. Static factories delegate to instance methods.

### Methods
```php
// Existing
Password::required()                              // static factory
Password::sometimes()                             // static factory
Password::min(int $size)                          // static factory
Password::default()                               // static factory

// Existing instance methods (unchanged)
->max(int $size)
->mixedCase()
->letters()
->numbers()
->symbols()
->uncompromised(?int $threshold)
->rules(Closure|string|array $rules)

// New field modifiers (from trait or direct)
Password::default()->nullable()
Password::default()->bail()
Password::default()->requiredIf('action', 'change_password')
// ... all conditional modifiers
```

### Usage examples
```php
'password'         => Password::required()->min(8)->mixedCase()->uncompromised(),
'new_password'     => Password::default()->requiredIf('action', 'change_password')->min(12),
'optional_pin'     => Password::default()->nullable()->min(4)->max(6)->numbers(),
```

---

## Parser Changes

### `ValidationRuleParser::explodeExplicitRule()`

Replace the current hardcoded `instanceof Date || Numeric || StringRule` checks with a single `FluentRule` check.

All FluentRule builders implement `IteratorAggregate`. The parser always iterates them, handling both string constraints and embedded rule objects uniformly:

```php
class StringRule implements Stringable, FluentRule, IteratorAggregate
{
    use Conditionable, Macroable, HasFieldModifiers, HasEmbeddedRules;

    protected array $constraints = ['string'];  // string rules
    protected array $rules = [];                // embedded rule objects

    protected function addRule(array|string|object $rules): static
    {
        if (is_object($rules)) {
            $this->rules[] = $rules;
        } else {
            $this->constraints = array_merge($this->constraints, Arr::wrap($rules));
        }

        return $this;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator([
            ...$this->constraints,
            ...$this->rules,
        ]);
    }

    public function __toString(): string
    {
        // Lossy — only returns string constraints. Embedded rule objects
        // (Unique, Exists, Enum, etc.) are NOT included. The parser uses
        // getIterator() which returns everything. This method exists for
        // userland code that casts to string directly.
        return implode('|', array_unique($this->constraints));
    }
}
```

The parser always iterates FluentRule builders:

```php
if ($rule instanceof FluentRule) {
    $rules = [];
    foreach ($rule as $r) {
        $rules[] = is_string($r) ? $r : $this->prepareRule($r, $attribute);
    }
    return $rules;
}
```

When no rule objects are embedded, `getIterator()` returns only strings — functionally identical to the old `explode('|', (string) $rule)` behavior.

**File, ImageFile, Email, and Password** don't implement `FluentRule`. They use the `Rule` interface with `passes()`/`message()` and are handled via `prepareRule()` as before. Their field modifiers are prepended to their internal iterator output (following Password's established pattern).

### `HasEmbeddedRules` trait

Database rules, enum, and value-list rules appear on builders where they make sense. Separate from `HasFieldModifiers` to avoid putting `unique()` on `BooleanRule`:

```php
trait HasEmbeddedRules
{
    public function unique(string $table, ?string $column = null): static
    {
        return $this->addRule(Rule::unique($table, $column ?? 'NULL'));
    }

    public function exists(string $table, ?string $column = null): static
    {
        return $this->addRule(Rule::exists($table, $column ?? 'NULL'));
    }

    // For complex cases (->ignore(), ->where(), etc.), use the escape hatch:
    // ->rule(Rule::unique('users', 'email')->ignore($user->id))

    public function enum(string $type, ?Closure $callback = null): static
    {
        $rule = Rule::enum($type);

        if ($callback) {
            $callback($rule);
        }

        return $this->addRule($rule);
    }

    public function in(array $values): static
    {
        return $this->addRule(Rule::in($values));
    }

    public function notIn(array $values): static
    {
        return $this->addRule(Rule::notIn($values));
    }

}
```

No separate abstract needed — both traits share `addRule()` from `HasFieldModifiers`. The `addRule()` method routes objects to `$this->rules[]` and strings to `$this->constraints[]`.

**Consumers:** `StringRule`, `Numeric`, `Date`, `Email`. Not on `BooleanRule` or `ArrayRule` — these types don't meaningfully need `unique()`, `exists()`, `enum()`, or `in()`/`notIn()`. They have `rule()` on `HasFieldModifiers` as an escape hatch if needed.

---

## Implementation Order

### Phase 1: Field modifiers on existing builders (build first)
1. Extract `Concerns\HasFieldModifiers` trait (with abstract `addRule(array|string|object)`)
2. Apply trait to `StringRule`, `Numeric`, `Date`
3. Expand `addRule()` signature from `array|string` to `array|string|object` (routes objects to `$rules` array)
4. Add `Macroable` to `StringRule` and `Numeric` (Date already has it) for consistency

Start here — this is the foundation everything else builds on. The existing `instanceof Date || Numeric || StringRule` parser checks still work at this stage. Embedded objects aren't used yet.

### Phase 2: Parser + FluentRule + IteratorAggregate
5. Create `FluentRule` marker interface
6. Have `StringRule`, `Numeric`, `Date` implement `FluentRule` and `IteratorAggregate`
7. Add `$rules` array and `getIterator()` to all three
8. Update parser to use `FluentRule` check with iterator-based handling
9. Extract `Concerns\HasEmbeddedRules` trait (`unique`, `exists`, `enum`, `in`, `notIn` — delegates to `addRule()`)
10. Apply `HasEmbeddedRules` to `StringRule`, `Numeric`, `Date`

### Phase 3: New builders
11. Refactor `ArrayRule` to use `$constraints` + `addRule()` pattern, implement `FluentRule` + `IteratorAggregate`, add `Macroable`
12. Add size methods (`min`, `max`, `between`, `exactly`, `list`, `requiredArrayKeys`)
13. Create `BooleanRule` with `HasFieldModifiers` + `accepted`/`declined` methods + `Macroable`

### Phase 4: File / ImageFile / Email / Password
14. Refactor `Password` to use `$modifiers` array instead of boolean properties, apply `HasFieldModifiers` trait, delegate static factories to instance methods
15. Apply same `$modifiers` + `HasFieldModifiers` pattern to `File` and `ImageFile`
16. Add `mimes()`, `mimetypes()` to File
17. Add dimension convenience methods to `ImageFile`
18. Apply `$modifiers` + `HasFieldModifiers` + `HasEmbeddedRules` + `max()` to `Email`

### Phase 5: Type-specific methods
19. Add format validators to `StringRule` (`url`, `uuid`, `ulid`, `ip`, `json`, `regex`, `date`, `dateFormat`, etc.)
20. Add comparison methods to `StringRule` (`confirmed`, `currentPassword`, `same`, `different`, `inArray`, `inArrayKeys`, `distinct`)
21. Add `confirmed`/`inArray`/`inArrayKeys`/`distinct` to `Numeric`

---

## Decisions

1. **Architecture: Option C** — each builder is self-contained. No cross-type composition via `Rule::string()->email()`. Use `Rule::email()` directly instead.
2. **`requiredIf` overloaded signature**: accepts `(Closure|bool)` to embed object, or `(string $field, mixed ...$values)` for string rule. Same for `excludeIf`, `excludeUnless`, `prohibitedIf`, `prohibitedUnless`.
3. **Field modifiers on File/Email/Password**: prepend to iterator output, following Password's established pattern.
4. **`requiredIf` name collision**: use the same name — static factory (`Rule::requiredIf()`) vs instance method (`->requiredIf()`) never collide.
5. **Type-contradictory methods removed from StringRule**: `boolean()`, `accepted()`, `declined()` belong on `Rule::boolean()`. `date()` and `dateFormat()` kept (validating a string is a parseable date is legitimate).
6. **`Rule::email()` gains `max()`, `unique()`, `exists()`**: covers the most common email validation pattern in one chain.
7. **Trait name**: `HasFieldModifiers`.
8. **`Rule::boolean()` as separate builder**: yes, common enough (111 occurrences in real-world app).
9. **PR strategy**: single PR. Build incrementally (follow the phase ordering below), but ship as one cohesive PR. Multiple small PRs are unlikely to all get merged — one well-crafted PR with a clear vision has a better shot.
10. **`anyOf` composition**: skip for now, `Rule::anyOf([...])` works fine standalone.
11. **Conditional modifiers**: ship the common set in v1 (~18 methods: core modifiers + requiredIf/Unless/With/WithAll/Without/WithoutAll + excludeIf/Unless/With/Without + prohibitedIf/Unless + prohibits). The rare ones (requiredIfAccepted, presentWithAll, missingUnless, etc.) are accessible via `->rule('present_with_all:foo,bar')`. Can be added later if there's demand.
12. **`rule()` API**: accepts `ValidationRule` object or `Closure`. Available on all builders.
13. **Parser safety**: `FluentRule` marker interface instead of duck-typing `Stringable`/`IteratorAggregate`.
14. **Two traits, split by purpose**: `HasFieldModifiers` (universal — all builders) has field modifiers + `rule()` escape hatch. `HasEmbeddedRules` (selective — `StringRule`, `Numeric`, `Date`, `Email`) has `unique()`, `exists()`, `enum()`, `in()`, `notIn()`. Not on `BooleanRule` or `ArrayRule` where these don't make sense.
15. **`in()`/`notIn()` embed objects**: use existing `In`/`NotIn` rule classes rather than generating string output. Handles quoting edge cases correctly.
16. **Unified `addRule(array|string|object)`**: single routing method on all builders. Strings go to `$constraints` (FluentRule) or `$modifiers` (Rule-interface builders). Objects go to `$rules` or `$modifiers`. Both traits share this one abstract.
17. **`Macroable` on all FluentRule builders**: `StringRule`, `Numeric`, `Date`, `ArrayRule`, `BooleanRule` all get `Macroable` for consistency (Date already has it).
18. **`__toString()` is intentionally lossy**: only returns string constraints. Embedded objects are dropped. The parser uses `getIterator()`. This makes it obvious when string casting is used incorrectly.
19. **`unique()`/`exists()` are simple**: table + column only. Complex cases (`.ignore()`, `.where()`) use `->rule(Rule::unique(...)->ignore(...))`.
20. **`Email->max()` is the line**: only `max()` is added as a string constraint directly on Email. All other string constraints use the escape hatch.
21. **Implementation order matters**: build the foundation (trait + modifiers) first, then parser, then new builders, then type-specific methods. But ship everything as one PR.

---

## Evaluation

### What's working well

- **Each builder is focused and self-contained.** `Rule::email()`, `Rule::boolean()`, `Rule::file()` each handle their own domain without god-object bloat.
- **Two clean traits** — `HasFieldModifiers` (field modifiers) and `HasEmbeddedRules` (database/enum/custom rules) — both delegate to a single unified `addRule(array|string|object)` method.
- **Zero breaking changes.** Everything is additive — existing APIs remain identical.
- **The `rule()` escape hatch** ensures no use case is impossible.
- **`IteratorAggregate` on all FluentRule builders** provides a single consistent parser path. When no objects are embedded, `getIterator()` returns only strings — functionally identical to the old string-based behavior.
- **Real-world validated.** Cross-referenced against 111 Laravel rules and a production app with 400+ validation rules.
- **Embedded rule objects** (`In`, `NotIn`, `Unique`, `Exists`, `Enum`) reuse existing battle-tested rule classes rather than reimplementing their serialization logic.

### What's awkward

- **`exclude_unless` is heavily used (106 occurrences in hihaho)** with enum values. The trait signature `excludeUnless(Closure|bool|string $field, mixed ...$values)` handles this, but make sure enum backed values serialize correctly in the string rule output.
- **Password's static factory pattern** (`Password::required()`, `Password::min(8)`) differs from the other builders (`Rule::string()`, `Rule::numeric()`). This is pre-existing and not worth changing.
- **Password refactor** — replacing boolean properties with `$modifiers` array is a minor internal breaking change. `$this->required` and `$this->sometimes` are `protected`, so only subclasses would be affected. Unlikely to cause real issues.

### What's missing

**All 111 documented Laravel rules are now covered** either directly on a builder or via the `rule()` escape hatch. The only standalone rules not on a builder are `dimensions` (accessible via `Rule::dimensions()` and `ImageFile` convenience methods) and `anyOf` (accessible via `Rule::anyOf()`).

---

## Notes & Future Considerations

### `when()` / `unless()` (from `Conditionable`)

All builders already use the `Conditionable` trait. This works with field modifiers out of the box:

```php
Rule::string()
    ->required()
    ->when($isAdmin, fn ($rule) => $rule->min(12))
    ->max(255)
```

This complements `requiredIf()` and friends — use `when()`/`unless()` for PHP-side conditions evaluated at rule-building time, use `requiredIf()`/`excludeUnless()` for conditions evaluated at validation time against the input data.

### Custom error messages

The spec does not address attaching custom error messages to fluent chains. Currently messages are defined separately:

```php
$messages = ['name.required' => 'Name is required'];
```

This is out of scope for v1. A future consideration could be something like `->message('required', 'Name is required')` but this would require deeper integration with the validator's message resolution. Leave for a follow-up.

### `Rule::forEach()` and nested validation

Fluent builders can be returned from `Rule::forEach()` callbacks since they implement `Stringable` and `IteratorAggregate`. The parser already handles them:

```php
'items.*' => Rule::forEach(fn () => Rule::string()->required()->max(255)),
```

This should work but needs explicit test coverage before shipping.

### `distinct()` on ArrayRule

`distinct` is typically applied to array *items* (e.g., `items.*.id => 'distinct'`), not to the array field itself. `Rule::array()->distinct()` would output `array|distinct` which isn't meaningful on the parent field.

**Resolution:** remove `distinct()` from ArrayRule. Users should apply it to the wildcard path:

```php
'items'       => Rule::array()->required()->min(1),
'items.*.id'  => Rule::string()->required()->distinct(),
```

### Parser test matrix

The iterator-based parser change touches every validation flow. The following tests are **required before merging Phase 1**:

```php
// 1. FluentRule with only string constraints (no objects)
'name' => Rule::string()->required()->min(2)->max(255)
// Expected: parser iterates, gets ['string', 'required', 'min:2', 'max:255']

// 2. FluentRule with mixed strings and objects
'email' => Rule::string()->required()->unique('users', 'email')
// Expected: parser iterates, gets ['string', 'required', <Unique object>]
// Unique object passed through prepareRule()

// 3. FluentRule nested inside an array of rules
'name' => ['sometimes', Rule::string()->min(2)]
// Expected: parser handles array, encounters FluentRule in array branch,
// iterates it, merges into ['sometimes', 'string', 'min:2']

// 4. FluentRule returned from Rule::forEach()
'items.*' => Rule::forEach(fn () => Rule::string()->required()->max(255))
// Expected: forEach callback returns FluentRule, parser handles it

// 5. FluentRule with Conditionable (when/unless)
'password' => Rule::string()->required()->when($isAdmin, fn ($r) => $r->min(12))->max(255)
// Expected: when() modifies builder in-place, final iteration includes min:12 conditionally

// 6. Backwards compat: existing usage without any new methods
'name' => Rule::string()->min(2)->max(255)
'age'  => Rule::numeric()->integer()->min(0)
'date' => Rule::date()->after('today')
// Expected: identical behavior to current framework — no regressions

// 7. FluentRule cast to string (userland)
(string) Rule::string()->required()->min(2)
// Expected: 'string|required|min:2'

// 8. FluentRule with embedded objects cast to string (lossy — by design)
(string) Rule::string()->required()->unique('users', 'email')
// Expected: 'string|required' — Unique object is NOT included in __toString()
// This is intentional. The parser uses getIterator() which returns everything.

// 9. FluentRule with rule() escape hatch
'slug' => Rule::string()->required()->rule(new UniqueSlugRule($post))
// Expected: parser iterates, gets ['string', 'required', <UniqueSlugRule object>]
// UniqueSlugRule passed through prepareRule()

// 10. FluentRule with enum() closure
'status' => Rule::string()->required()->enum(Status::class, fn ($e) => $e->only([Status::Active]))
// Expected: parser iterates, gets ['string', 'required', <Enum object with only()>]

// 11. Numeric enum with int-backed enum
'priority' => Rule::numeric()->required()->enum(Priority::class)
// Expected: Enum rule correctly validates int-backed enum values (1, 2, 3), not strings

// 12. ImageFile ratio as string
'photo' => Rule::imageFile()->required()->ratio('16/9')
// Expected: passes string ratio through to Dimensions rule, not float 1.7777...
```

All 12 cases must pass before the parser change ships. Cases 1-3 and 6 are the critical path — if those break, the framework is broken.

### Dual storage: `$constraints` vs `$rules`

FluentRule builders have two storage arrays:
- `$constraints` — string rules (e.g., `'string'`, `'required'`, `'max:255'`)
- `$rules` — embedded rule objects (e.g., `Unique`, `Exists`, `Enum`, custom `ValidationRule`)

The unified `addRule(array|string|object)` method routes automatically — strings go to `$constraints`, objects go to `$rules`. Contributors don't need to choose which array to use; they just call `addRule()` and the routing handles it.

`getIterator()` merges both arrays. `__toString()` is intentionally lossy — it only returns `$constraints` (strings). Embedded objects are dropped. This is by design: the parser uses `getIterator()`, and keeping `__toString()` dumb makes it obvious when someone accidentally uses string casting instead of the iterator path.
