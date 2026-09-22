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

namespace Visol\Handlebars\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Visol\Handlebars\Engine\HandlebarsEngine;
use Visol\Handlebars\Exception\TemplateNotFoundException;
use Visol\Handlebars\Rendering\HandlebarsContext;
use Visol\Handlebars\Tests\Functional\AbstractTestCase;
use Visol\Handlebars\View\HandlebarsView;
use Visol\Handlebars\ViewHelpers\RenderViewHelper;

#[TestDox('The RenderViewHelper')]
final class RenderViewHelperTest extends AbstractTestCase
{
    #[Test]
    public function throwsExceptionIfTemplateCanNotBeFoundDueToMissingTemplatesRootPath(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template BaseExample not found with templatesRootPath null');
        $this->expectExceptionCode(0);

        $this->callViewHelper('BaseExample', '');
    }

    #[Test]
    public function throwsExceptionIfTemplateCanNotBeFoundDueToMissingTemplateFile(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template NoneExistingTemplate not found with templatesRootPath EXT:handlebars/Tests/Functional/Fixtures/Frontend/Templates/');
        $this->expectExceptionCode(0);

        $this->callViewHelper('NoneExistingTemplate');
    }

    #[Test]
    public function rendersBasicExampleViaViewHelper(): void
    {
        $result = $this->callViewHelper('BaseExample');
        self::assertSame('Result from base example.', $result);
    }

    private function callViewHelper(
        string $template,
        ?string $settings = null,
        ?string $data = null,
    ): string {
        $typoScriptSetup = [
            'page = PAGE',
            'page.config.disableAllHeaderCode = 1',
            'page.10 = FLUIDTEMPLATE',
            'page.10.template = TEXT',
            'page.10.template.value = ' . $this->createFluidTemplate($template, $settings, $data),
        ];

        $this->setUpFrontendRootPage(1, [], [
            'config' => implode(PHP_EOL, $typoScriptSetup),
        ]);

        return $this->fetchContentForPage(1);
    }

    private function createFluidTemplate(
        string $template,
        ?string $settings,
        ?string $data,
    ): string {
        $viewHelperCall = '<handlebars:render template="' . $template . '"';

        if ($settings === null) {
            $viewHelperCall .= ' settings="{handlebars: {templatesRootPath: \'EXT:handlebars/Tests/Functional/Fixtures/Frontend/Templates/\'}}"';
        } else {
            $viewHelperCall .= ' settings="' . $settings . '"';
        }

        if ($data !== null) {
            $viewHelperCall .= ' data="' . $data . '"';
        }

        $viewHelperCall .= ' />';


        return '<html xmlns:handlebars="http://typo3.org/ns/Visol/Handlebars/ViewHelpers" data-namespace-typo3-fluid="true">' . $viewHelperCall;
    }
}
