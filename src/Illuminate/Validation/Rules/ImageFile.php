<?php

namespace Illuminate\Validation\Rules;

class ImageFile extends File
{
    /**
     * The dimension constraints for the image.
     *
     * @var \Illuminate\Validation\Rules\Dimensions|null
     */
    protected $dimensionConstraints = null;

    /**
     * Create a new image file rule instance.
     *
     * @param  bool  $allowSvg
     */
    public function __construct($allowSvg = false)
    {
        if ($allowSvg) {
            $this->rules('image:allow_svg');
        } else {
            $this->rules('image');
        }
    }

    /**
     * The dimension constraints for the uploaded file.
     *
     * @param  \Illuminate\Validation\Rules\Dimensions  $dimensions
     * @return $this
     */
    public function dimensions($dimensions)
    {
        // Remove any previously registered Dimensions from customRules
        // to avoid duplicate/conflicting constraints.
        $this->customRules = array_values(array_filter(
            $this->customRules,
            fn ($rule) => ! $rule instanceof Dimensions,
        ));

        $this->dimensionConstraints = $dimensions;

        $this->rules($dimensions);

        return $this;
    }

    /**
     * The image must have the given exact width.
     */
    public function width(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->width($value);

        return $this;
    }

    /**
     * The image must have the given exact height.
     */
    public function height(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->height($value);

        return $this;
    }

    /**
     * The image must have a minimum width.
     */
    public function minWidth(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->minWidth($value);

        return $this;
    }

    /**
     * The image must have a minimum height.
     */
    public function minHeight(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->minHeight($value);

        return $this;
    }

    /**
     * The image must not exceed the given width.
     */
    public function maxWidth(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->maxWidth($value);

        return $this;
    }

    /**
     * The image must not exceed the given height.
     */
    public function maxHeight(int $value): static
    {
        $this->buildDimensions()->dimensionConstraints->maxHeight($value);

        return $this;
    }

    /**
     * The image must have the given aspect ratio.
     */
    public function ratio(float|string $value): static
    {
        $this->buildDimensions()->dimensionConstraints->ratio($value);

        return $this;
    }

    /**
     * The image must have a minimum aspect ratio.
     */
    public function minRatio(float|string $value): static
    {
        $this->buildDimensions()->dimensionConstraints->minRatio($value);

        return $this;
    }

    /**
     * The image must not exceed the given aspect ratio.
     */
    public function maxRatio(float|string $value): static
    {
        $this->buildDimensions()->dimensionConstraints->maxRatio($value);

        return $this;
    }

    /**
     * The image aspect ratio must be between the given min and max.
     */
    public function ratioBetween(float|string $min, float|string $max): static
    {
        $this->buildDimensions()->dimensionConstraints->ratioBetween($min, $max);

        return $this;
    }

    /**
     * Ensure a Dimensions instance is created and registered.
     */
    protected function buildDimensions(): static
    {
        if (! $this->dimensionConstraints) {
            $this->dimensionConstraints = new Dimensions;

            $this->rules($this->dimensionConstraints);
        }

        return $this;
    }
}
