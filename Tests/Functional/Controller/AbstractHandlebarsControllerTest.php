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

namespace Visol\Handlebars\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use Visol\Handlebars\DataProvider\TyposcriptDataProvider;
use Visol\Handlebars\Exception\NoTemplateConfiguredException;
use Visol\Handlebars\Exception\TemplateNotFoundException;
use Visol\Handlebars\Tests\Functional\AbstractTestCase;

#[TestDox('The AbstractHandlebarsController')]
final class AbstractHandlebarsControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad = [
            'typo3conf/ext/handlebars/Tests/Functional/Fixtures/Frontend/example_extension/',
        ];
        $this->coreExtensionsToLoad = [
            'typo3/cms-fluid-styled-content',
        ];

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/Content.csv');

        // Ensure clean state for compiled templates.
        GeneralUtility::rmdir($this->getInstancePath() . '/typo3temp/handlebars/', true);
    }

    #[Test]
    public function throwsExceptionIfTemplateCanNotBeFoundDueToMissingTemplateConfiguration(): void
    {
        $this->expectException(NoTemplateConfiguredException::class);
        $this->expectExceptionMessage('No template configured for HandlebarsEngine');
        $this->expectExceptionCode(8130705640);

        $this->renderContentElement();
    }

    #[Test]
    public function throwsExceptionIfTemplateCanNotBeFoundDueToEmptyTemplatesRootPath(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template Example not found with templatesRootPath null');
        $this->expectExceptionCode(0);

        $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = Example',
        ]);
    }

    #[Test]
    public function rendersTemplate(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = Example',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
        ]);
        self::assertSame('Result from action example.', $result);
    }

    /**
     * Right now variables are not supported/used.
     */
    #[Test]
    public function canAssignMultiple(): void
    {
        $this->updateCType('exampleextension_assignmultiple');

        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = AssignMultiple',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
        ]);
        self::assertSame(',', $result);
    }

    #[Test]
    public function canAccessVariablesThroughTyposcriptDataProvider(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = VariablesThroughTyposcriptDataProvider',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.variables.variable1 = value1',
        ]);
        self::assertSame('value1', $result);
    }

    #[Test]
    public function canAccessVariablesThroughCustomConfiguredDataProvider(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = VariablesThroughCustomConfiguredDataProvider',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.dataProviders.10 = Visol\ExampleExtension\DataProvider\CustomDataProvider',
        ]);
        self::assertSame('value from provider', $result);
    }

    #[Test]
    public function ignoresCallingUnavailablePartial(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = CallPartial',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
        ]);
        self::assertSame('', $result);
    }

    #[Test]
    public function canRenderPartial(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = CallPartial',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.partialsRootPath = EXT:example_extension/Resources/Private/Partials/',
        ]);
        self::assertSame('Partial Content', $result);
    }

    #[Test]
    public function canUseDefaultHelperJson(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = DefaultHelperJson',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.dataProviders.10 = Visol\ExampleExtension\DataProvider\JsonDataProvider',
        ]);
        self::assertSame('{"array":{"key1":"value1"},"string":"string","integer":10,"float":10.25,"object":{"property1":"value1"}}', $result);
    }

    #[Test]
    public function canUseCustomHelper(): void
    {
        $this->updateCType('exampleextension_customhelper');

        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = CustomHelper',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
        ]);
        self::assertSame('some value via custom helper', $result);
    }

    #[Test]
    public function storesCompiledTemplatesInConfiguredFolder(): void
    {
        $result = $this->renderContentElement([
            'plugin.tx_exampleextension.settings.template = Example',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.tempPath = typo3temp/handlebars_2/',
        ]);

        $compiledFiles = glob($this->getInstancePath() . '/typo3temp/handlebars_2/*.php') ?: [];
        self::assertCount(1, $compiledFiles);
    }

    #[Test]
    public function reUsesCompiledFile(): void
    {
        $instructions = [
            'plugin.tx_exampleextension.settings.template = Example',
            'plugin.tx_exampleextension.settings.templatesRootPath = EXT:example_extension/Resources/Private/Templates/Example/',
            'plugin.tx_exampleextension.settings.tempPath = typo3temp/handlebars_2/',
        ];

        $result = $this->renderContentElement($instructions);

        $compiledFilesFirstRune = glob($this->getInstancePath() . '/typo3temp/handlebars_2/*.php') ?: [];

        $result = $this->renderContentElement($instructions);

        $compiledFilesSecondRun = glob($this->getInstancePath() . '/typo3temp/handlebars_2/*.php') ?: [];
        self::assertSame($compiledFilesFirstRune, $compiledFilesSecondRun);
    }

    private function renderContentElement(array $additionalTypoScriptSetup = []): string
    {
        $typoScriptSetup = [
            '@import "EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript"',
            'lib.contentElement.templateRootPaths.10 = EXT:handlebars/Tests/Functional/Fixtures/Frontend/FluidStyledContent/Templates/',
            'lib.contentElement.layoutRootPaths.10 = EXT:handlebars/Tests/Functional/Fixtures/Frontend/FluidStyledContent/Layouts/',

            'plugin.tx_exampleextension.settings.tempPath = typo3temp/handlebars/',

            'page = PAGE',
            'page.config.disableAllHeaderCode = 1',
            'page.10 < styles.content.get',
            ...$additionalTypoScriptSetup,
        ];

        $this->setUpFrontendRootPage(1, [], [
            'config' => implode(PHP_EOL, $typoScriptSetup),
        ]);

        return $this->fetchContentForPage(1);
    }

    private function updateCType(string $newCType): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->update('tt_content');
        $queryBuilder->set('CType', $newCType);
        $queryBuilder->executeStatement();
    }
}
