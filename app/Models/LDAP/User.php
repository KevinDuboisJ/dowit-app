<?php

namespace App\Models\LDAP;


class User
{
    protected array $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): User
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
}
