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

namespace FacturaScripts\Plugins\Comisiones\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Dinamic\Model\Agente;

/**
 * Penalización aplicada a la comisión al aplicar un descuento.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ComisionPenalizacion extends ModelClass
{
    use ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $id;

    /**
     * Enlace al modelo de agente.
     *
     * @var string
     */
    public $codagente;

    /**
     * Enlace al modelo de empresa.
     *
     * @var integer
     */
    public $idempresa;

    /**
     * % de descuento desde.
     *
     * @var float
     */
    public $dto_desde;

    /**
     * % de descuento hasta.
     *
     * @var float
     */
    public $dto_hasta;

    /**
     * Porcentaje de penalización.
     *
     * @var float
     */
    public $penalizacion;

    public function clear(): void
    {
        parent::clear();
        $this->dto_desde = 1.00;
        $this->dto_hasta = 100.00;
        $this->penalizacion = 100.00;
    }

    public function install(): string
    {
        new Agente();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'comisionespenalizaciones';
    }

    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
