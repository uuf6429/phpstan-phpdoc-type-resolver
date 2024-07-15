<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use Closure;
use uuf6429;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Cases\{Case1, Case2};

abstract class TypeResolverTestFixture
{
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
