<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use Closure;
use uuf6429;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Cases\{Case1, Case2};

/**
 * @phpstan-type TColors array{red: '#F00', green: '#0F0', blue: '#00F'}
 */
abstract class TypeResolverTestFixture
{
    public const TYPE_A = 'a';
    public const TYPE_B = 'b';
    private const TYPE_C = 'c';

    /**
     * @return void Returns a **digital representation** of _nothingness manifested_.
     *              Mind the dragons.
     */
    abstract public function returnVoid(): void;

    /**
     * @return ?string
     */
    abstract public function returnNullableString(): ?string;

    /**
     * @return bool|integer
     */
    abstract public function returnBoolOrInteger(): bool|int;

    /**
     * @return Cases\Case1
     */
    abstract public function returnImplicitNamespaceClass(): Cases\Case1;

    /**
     * @return Case1|Case2
     */
    abstract public function returnImportedGroupedNamespaceClass(): Case1|Case2;

    /**
     * @return self
     */
    abstract public function returnSelf(): self;

    /**
     * @return static
     */
    abstract public function returnStatic(): TypeResolverTestFixture;

    /**
     * @return $this
     */
    abstract public function returnThis(): self;

    /**
     * @return array{1: list<Case1>, 2?: Case2[]}
     */
    abstract public function returnArrayOfGroupedCases(): array;

    /**
     * @return object{'jumpingCases': null|(Case1&Cases\JumpingCaseInterface)[]}
     */
    abstract public function returnCasesJumpingWrappedInObject(): object;

    /**
     * @return ($cond is true ? callable : "text")
     */
    abstract public function returnCallableOrTextConditionally(bool $cond): callable|string;

    /**
     * @return int<0, max>
     */
    abstract public function returnRandomInt(): int;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return new<T>
     */
    abstract public function createClass(string $class): object;

    /**
     * @template TColorKey of key-of<TColors>
     * @param TColorKey $colorName
     * @return null|TColors[TColorKey]
     */
    abstract public function translateColor(string $colorName): ?string;

    /**
     * @return callable(int, bool $named): string
     */
    abstract public function returnCallableWithTypedArgs(): callable;

    /**
     * @return callable<T>(): T
     */
    abstract public function returnCallableWithTemplates(): callable;

    /**
     * @return self::TYPE_A|static::TYPE_B
     */
    abstract public function returnOneClassConstant(): string;

    /**
     * @return list<self::TYPE_*>
     */
    abstract public function returnAllClassConstants(): array;

    public static function getTypeResolverTestClosureReturningString(): Closure
    {
        /**
         * @return string
         */
        return static function (): string {
            return 'hoi';
        };
    }
}
