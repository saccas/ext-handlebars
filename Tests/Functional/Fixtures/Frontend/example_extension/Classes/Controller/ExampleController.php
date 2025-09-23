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

namespace Visol\ExampleExtension\Controller;

use Psr\Http\Message\ResponseInterface;
use Visol\Handlebars\Controller\AbstractHandlebarsController;
use Visol\Handlebars\HelperRegistry;

final class ExampleController extends AbstractHandlebarsController
{
    public function exampleAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function assignMultipleAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'variable1' => 'value1',
            'variable2' => 'value2',
        ]);
        return $this->htmlResponse();
    }

    public function customHelperAction(): ResponseInterface
    {
        HelperRegistry::getInstance()->register('helper', function (string $value) {
            return $value . ' via custom helper';
        });
        return $this->htmlResponse();
    }
}
