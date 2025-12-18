<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;

/**
 * Add new fields in the modal window of the document header
 *   - editcomision: Allows the user to modify the agent commission.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
        if ($model->hasColumn('editcomision') && isset($formData['editcomision'])) {
            $model->editcomision = ($formData['editcomision'] === 'true') ?? false;
        }
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['editcomision'];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        if ($field === 'editcomision') {
            return $this->editComision($model);
        }
        return null;
    }

    private function editComision(SalesDocument $model): string
    {
        if (false === $model->hasColumn('editcomision')) {
            return '';
        }

        $options = [];
        foreach (['false', 'true'] as $row) {
            $txt = ($row === 'true') ? 'yes' : 'no';
            $options[] = ($row == $model->editcomision)
                ? '<option value="' . $row . '" selected>' . Tools::trans($txt) . '</option>'
                : '<option value="' . $row . '">' . Tools::trans($txt) . '</option>';
        }

        $attributes = $model->editable ? 'name="editcomision" required' : 'disabled';
        return '<div class="col-sm-4">'
            . '<div class="mb-3">' . Tools::trans('edit-commission')
                . '<select ' . $attributes . ' class="form-select"/>'
                    . implode('', $options)
                . '</select>'
            . '</div>'
        . '</div>';
    }
}
