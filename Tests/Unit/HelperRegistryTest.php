<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace Visol\Handlebars\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Visol\Handlebars\HelperRegistry;

#[CoversClass(HelperRegistry::class)]
#[TestDox('The HelperRegistry')]
final class HelperRegistryTest extends TestCase
{
    #[Test]
    public function returnsSameInstance(): void
    {
        self::assertSame(
            HelperRegistry::getInstance(),
            HelperRegistry::getInstance()
        );
    }

    #[Test]
    public function canRegisterHelper(): void
    {
        $helper = function () {};

        $subject = HelperRegistry::getInstance();
        $subject->register('example', $helper);

        self::assertSame([
            'example' => $helper,
        ], $subject->getHelpers());
    }

    #[Test]
    public function canUnregisterHelper(): void
    {
        $helper = function () {};

        $subject = HelperRegistry::getInstance();
        $subject->register('example', $helper);
        $subject->unregister('example');

        self::assertSame([], $subject->getHelpers());
    }
}
