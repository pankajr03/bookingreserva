<?php

declare(strict_types=1);

namespace BookneticApp\Providers\Mappers;

use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use RuntimeException;

class DTOMapper
{
    /**
     * @template TClass of object
     * @param array $data
     * @param class-string<TClass> $dtoClass
     * @param bool $strict Throw on type/shape mismatch (default: true)
     * @return TClass
     * @throws ReflectionException
     */
    public static function map(array $data, string $dtoClass): object
    {
        if (!class_exists($dtoClass)) {
            throw new RuntimeException("DTO class $dtoClass does not exist.");
        }

        /** @var TClass $dto */
        $dto = new $dtoClass();

        foreach ($data as $key => $value) {
            if (!property_exists($dto, $key)) {
                continue;
            }

            $type = (new ReflectionProperty($dtoClass, $key))->getType();

            if ($type !== null) {
                $value = self::convertValue($value, $type, "$dtoClass::$key");
            }

            $dto->$key = $value;
        }

        return $dto;
    }

    private static function convertValue($value, ReflectionType $type, string $ctx)
    {
        if ($value === null) {
            if (!$type->allowsNull()) {
                throw new RuntimeException("Null given for non-nullable $ctx.");
            }

            return null;
        }

        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $name = $type->getName();

        switch ($name) {
            case 'array':
                if (is_array($value)) {
                    return $value;
                }

                if (!is_string($value)) {
                    throw new RuntimeException("Expected array for $ctx, got " . gettype($value) . ".");
                }

                $decoded = json_decode($value, true);

                if (!is_array($decoded)) {
                    throw new RuntimeException("Expected array or JSON array for $ctx.");
                }

                return $decoded;

            case 'int':
            case 'float':
            case 'string':
            case 'bool':
                settype($value, $name);

                return $value;

            default:
                if (!($value instanceof $name)) {
                    throw new RuntimeException("Expected instance of $name for $ctx.");
                }

                return $value;
        }
    }
}
