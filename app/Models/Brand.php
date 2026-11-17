<?php
declare(strict_types=1);

namespace NVRO\Models;

/** A vehicle manufacturer. One Brand has many Vehicle objects. */
final class Brand extends Entity
{
    private string $name;
    private string $countryOfOrigin;

    public function __construct(string $name = '', string $countryOfOrigin = '')
    {
        $this->name = $name;
        $this->countryOfOrigin = $countryOfOrigin;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCountryOfOrigin(): string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(string $countryOfOrigin): void
    {
        $this->countryOfOrigin = $countryOfOrigin;
    }

    public function describe(): string
    {
        return $this->name;
    }
}
