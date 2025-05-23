<?php

namespace Visol\Handlebars\Engine;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Fabien Udriot <fabien.udriot@visol.ch>, Visol digitale Dienstleistungen GmbH
 *  (c) 2017 Alessandro Bellafronte <alessandro@4eyes.ch>, 4eyes GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Visol\Handlebars\DataProvider\DataProviderInterface;
use Visol\Handlebars\Exception\NoTemplateConfiguredException;
use Visol\Handlebars\Exception\TemplateNotFoundException;
use Visol\Handlebars\HelperRegistry;
use LightnCandy\LightnCandy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class HandlebarsEngine
 */
class HandlebarsEngine
{
    protected array $settings;

    protected string $extensionKey;

    protected string $controllerName;

    protected ?string $templatesRootPath;

    protected ?string $partialsRootPath;

    protected ?string $template;
    
    protected array $dataProviders;

    protected array $additionalData;

    protected string $tempPath;

    /**
     * HandlebarsEngine constructor.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->templatesRootPath = $settings['templatesRootPath'] ?? null;
        $this->partialsRootPath = $settings['partialsRootPath'] ?? null;
        $this->template = $settings['template'] ?? $settings['templatePath'] ?? null;
        $this->dataProviders = $settings['dataProviders'] ?? [];
        $this->additionalData = $settings['additionalData'] ?? [];
        $this->tempPath = Environment::getProjectPath() . '/' . $settings['tempPath'];
    }

    /**
     * Runs the compiling process and returns the rendered html
     */
    public function compile(): string
    {
        $renderer = $this->getRenderer();
        $data = $this->getData();
        return $renderer($data);
    }

    public function getData(): array
    {
        $data = [];

        if (!isset($this->settings['dataProviders'])) {
            $this->settings['dataProviders'] = $this->getDefaultDataProviders();
        }

        foreach ($this->settings['dataProviders'] as $dataProviderClass) {
            /** @var DataProviderInterface $dataProvider */
            $dataProvider = GeneralUtility::makeInstance($dataProviderClass, $this->settings);
            $data = array_merge_recursive($data, $dataProvider->provide());
        }

        return array_merge_recursive($data, $this->additionalData);
    }

    /**
     * Compiles the template, stores the output in a cache-file, and returns its callable content
     */
    public function getRenderer(): callable
    {
        if (!isset($this->template)) {
            throw new NoTemplateConfiguredException('No template configured for HandlebarsEngine', 8130705640);
        }
        return $this->getRendererForTemplate($this->template);
    }

    public function getRendererForTemplate(string $template): callable
    {
        $templatePathAndFilename = $this->getTemplatePathAndFilename($template);
        if (!isset($templatePathAndFilename)) {
            throw new TemplateNotFoundException($template, $this->templatesRootPath);
        }
        
        $compiledCodePathAndFilename = $this->getCompiledCodePathAndFilename($templatePathAndFilename);

        if (!is_file($compiledCodePathAndFilename) || $this->isBackendUserOnline()) { // if we have a BE login always compile the template

            // Compiling to PHP Code
            $compiledCode = LightnCandy::compile($this->getTemplateCode($templatePathAndFilename), $this->getOptions());

            // Save the compiled PHP code into a php file
            file_put_contents($compiledCodePathAndFilename, '<?php ' . $compiledCode . '?>');
        }

        // Returning the callable php file
        return include($compiledCodePathAndFilename);
    }

    protected function getOptions(): array
    {
        $helpers = $this->getViewHelpers();

        return [
            // Definition of flags (Docs: https://github.com/zordius/lightncandy#compile-options)
            'flags' => LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_RUNTIMEPARTIAL,
            // Provisioning of custom helpers
            'helpers' => $helpers,
            // Registration of a partial-resolver to provide support for partials
            'partialresolver' => function ($cx, $name) {
                return $this->getPartialCode($cx, $name);
            }
        ];
    }

    protected function getDefaultHelpers(): array
    {
        return [
            'json' => function ($context) {
                return json_encode($context, JSON_HEX_APOS);
            },
            'lookup' => function ($labels, $key) {
                return $labels[$key] ?? '';
            }
        ];
    }

    /**
     * Returns the content of the current template file
     */
    protected function getTemplateCode($templatePathAndFilename): string
    {
        return file_get_contents($templatePathAndFilename);
    }

    /**
     * Returns the content of a given partial
     *
     * @param $cx
     * @param $name
     */
    protected function getPartialCode($cx, $name): string
    {
        $partialContent = '';
        $partialFileNameAndPath = $this->getPartialPathAndFileName($name);
        if (file_exists($partialFileNameAndPath)) {
            return file_get_contents($partialFileNameAndPath);
        }
        return $partialContent;
    }

    /**
     * Returns the filename and path of the cache file
     */
    protected function getCompiledCodePathAndFilename(string $templatePathAndFilename): string
    {
        // Creates the directory if not existing
        if (!is_dir($this->tempPath)) {
            GeneralUtility::mkdir_deep($this->tempPath);
        }

        $basename = basename($templatePathAndFilename);
        $timeStamp = filemtime($templatePathAndFilename);

        return $this->tempPath . $basename . '_' . $timeStamp . '_' . sha1($templatePathAndFilename) . '.php';
    }

    /**
     * Returns the template filename and path
     */
    protected function getTemplatePathAndFilename(string $template): ?string
    {
        $candidates = [$template];
        if (isset($this->templatesRootPath)) {
            $candidates[] = $this->templatesRootPath . $template;
        }
        
        return $this->findHbsFile($candidates);
    }

    /**
     * Returns filename and path for a given partial name.
     * 1. Lookup below partialsRootPath
     * 2. Lookup below templatesRootPath
     */
    protected function getPartialPathAndFileName(string $name): ?string
    {
        $candidates = [$name];
        if (isset($this->partialsRootPath)) {
            $candidates[] = $this->partialsRootPath . $name;
        }
        if (isset($this->templatesRootPath)) {
            $candidates[] = $this->templatesRootPath . $name;
        }
        
        return $this->findHbsFile($candidates);
    }

    protected function findHbsFile(array $basenameCandidates): ?string
    {
        foreach ($basenameCandidates as $basenameCandidate) {
            $candidates = [
                $basenameCandidate,
                $basenameCandidate . '.hbs'
            ];
            
            foreach($candidates as $candidate) {
                $pathAndFilename = GeneralUtility::getFileAbsFileName($candidate);
                if (is_file($pathAndFilename)) {
                    return $pathAndFilename;
                }
            }
        }

        return null;
    }
    
    /**
     * Returns backend user online status
     */
    protected function isBackendUserOnline(): bool
    {
        return $this->getBackendUser() !== null
            && (int)$this->getBackendUser()->user['uid'] > 0;
    }

    /**
     * Returns an instance of the current Backend User.
     */
    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getDefaultDataProviders(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return (array)$extensionConfiguration->get('handlebars', 'defaultDataProviders');
    }

    protected function getViewHelpers(): array
    {
        $helpers = array_merge(
            $this->getDefaultHelpers(),
            HelperRegistry::getInstance()->getHelpers()
        );
        array_walk($helpers, fn($helperFunction) => \Closure::bind($helperFunction, $this, $this));
        return $helpers;
    }
}
