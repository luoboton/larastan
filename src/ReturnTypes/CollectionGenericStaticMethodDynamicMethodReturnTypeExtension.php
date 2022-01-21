<?php

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

class CollectionGenericStaticMethodDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if ($methodReflection->getDeclaringClass()->getName() === EloquentCollection::class) {
            return false;
        }

        return in_array($methodReflection->getName(), [
            'chunk', 'chunkWhile', 'collapse', 'combine',
            'countBy', 'crossJoin', 'flatMap', 'flip',
            'groupBy', 'keyBy', 'keys',
            'make', 'map', 'mapInto',
            'mapToDictionary', 'mapToGroups',
            'mapWithKeys', 'mergeRecursive', 'pad', 'pluck',
            'pop', 'random', 'shift', 'sliding', 'split',
            'splitIn', 'values', 'wrap', 'zip',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (! $returnType instanceof ObjectType && ! $returnType instanceof UnionType) {
            return $returnType;
        }

        /** @var ObjectType $calledOnType */
        $calledOnType = $scope->getType($methodCall->var);

        $classReflection = $calledOnType->getClassReflection();

        if ($classReflection === null) {
            return $returnType;
        }

        // If it's called on Support collection, just return.
        if ($classReflection->getName() === Collection::class) {
            return $returnType;
        }

        // If it's a UnionType, traverse the types and try to find a collection object type
        if ($returnType instanceof UnionType) {
            return $returnType->traverse(function (Type $type) use ($classReflection) {
                if ($type instanceof GenericObjectType && (($innerReflection = $type->getClassReflection())) !== null) {
                    return $this->handleGenericObjectType($classReflection, $innerReflection);
                }

                return $type;
            });
        }

        $returnTypeClassReflection = $returnType->getClassReflection();

        if ($returnTypeClassReflection === null) {
            return $returnType;
        }

        return $this->handleGenericObjectType($classReflection, $returnTypeClassReflection);
    }

    private function handleGenericObjectType(ClassReflection $classReflection, ClassReflection $returnTypeClassReflection): GenericObjectType
    {
        $genericTypes = $returnTypeClassReflection->typeMapToList($returnTypeClassReflection->getActiveTemplateTypeMap());

        $genericTypes = array_map(static function (Type $type) use ($classReflection) {
            return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($classReflection): Type {
                if ($type instanceof UnionType || $type instanceof IntersectionType) {
                    return $traverse($type);
                }

                if ($type instanceof GenericObjectType && (($innerTypeReflection = $type->getClassReflection()) !== null)) {
                    return new GenericObjectType($classReflection->getName(), $innerTypeReflection->typeMapToList($innerTypeReflection->getActiveTemplateTypeMap()));
                }

                return $traverse($type);
            });
        }, $genericTypes);

        return new GenericObjectType($classReflection->getName(), $genericTypes);
    }
}
