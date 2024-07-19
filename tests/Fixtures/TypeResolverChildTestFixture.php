<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

abstract class TypeResolverChildTestFixture extends TypeResolverTestFixture
{
    /**
     * @return parent
     */
    abstract public function returnParent(): object;
}
