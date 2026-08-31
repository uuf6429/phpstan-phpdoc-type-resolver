<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * The counterpart of {@see TemplateGenericTypeNode} returned by the {@see TypeResolver}, when all generic types
 * (templates) have been resolved to an actual existing class/type that is also itself concrete.
 */
class ConcreteGenericTypeNode extends AbstractGenericTypeNode {}
