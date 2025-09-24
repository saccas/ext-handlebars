<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Visol\ExampleExtension\Controller\ExampleController;

ExtensionUtility::configurePlugin(
    'example_extension',
    'ControllerExample',
    [
        ExampleController::class => 'example',
    ],
    [
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionUtility::configurePlugin(
    'example_extension',
    'AssignMultiple',
    [
        ExampleController::class => 'assignMultiple',
    ],
    [
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionUtility::configurePlugin(
    'example_extension',
    'CustomHelper',
    [
        ExampleController::class => 'customHelper',
    ],
    [
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
