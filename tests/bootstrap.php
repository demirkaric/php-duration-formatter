<?php

declare(strict_types=1);

namespace PHPUnit\Framework\Attributes {
    if (!class_exists(DataProvider::class)) {
        #[\Attribute(\Attribute::TARGET_CLASS)]
        final class CoversClass
        {
            public function __construct(...$args)
            {
            }
        }

        #[\Attribute(\Attribute::TARGET_METHOD)]
        final class DataProvider
        {
            public function __construct(...$args)
            {
            }
        }

        #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
        final class Group
        {
            public function __construct(...$args)
            {
            }
        }
    }
}

namespace {
    require_once __DIR__ . '/../src/TimeDuration.php';
}
