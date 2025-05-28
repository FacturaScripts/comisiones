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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;

/**
 * List of Commissions Settlement.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class LiquidacionComision extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * id of agent.
     *
     * @var string
     */
    public $codagente;

    /**
     * @var string
     */
    public $codserie;

    /**
     * Date of creation of the settlement.
     *
     * @var string
     */
    public $fecha;

    /**
     * @var int
     */
    public $idempresa;

    /**
     * id of generate invoice.
     *
     * @var int
     */
    public $idfactura;

    /**
     * @var int
     */
    public $idliquidacion;

    /**
     * @var string
     */
    public $observaciones;

    /**
     * Total amount of the commission settlement.
     *
     * @var double
     */
    public $total;

    public function clear()
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->total = 0.0;
    }

    /**
     * Calculate the total commission amount of a settlement
     *
     * @param int $code
     * @return bool
     */
    public function calculateTotalCommission(int $code): bool
    {
        $sql = 'UPDATE ' . self::tableName()
            . ' SET total = COALESCE('
            . '(SELECT SUM(totalcomision)'
            . ' FROM ' . FacturaCliente::tableName()
            . ' WHERE idliquidacion = ' . self::$dataBase->var2str($code) . ')'
            . ',0)'
            . ' WHERE idliquidacion = ' . self::$dataBase->var2str($code);

        return self::$dataBase->exec($sql);
    }

    /**
     * Generates an supplier invoice with this settlement.
     *
     * @return bool
     */
    public function generateInvoice(): bool
    {
        if (null !== $this->idfactura) {
            return true;
        }

        $agent = $this->getAgent();
        $contact = $agent->getContact();
        if (empty($contact->codproveedor)) {
            Tools::log()->warning('agent-dont-have-associated-supplier');
            return false;
        }

        $invoice = new FacturaProveedor();
        $invoice->setSubject($contact->getSupplier());
        $invoice->codserie = $this->codserie;
        $invoice->fecha = $this->fecha;

        $warehouse = new Almacen();
        foreach ($warehouse->all() as $alm) {
            if ($alm->idempresa == $this->idempresa) {
                $invoice->codalmacen = $alm->codalmacen;
                $invoice->idempresa = $alm->idempresa;
                break;
            }
        }

        if ($invoice->save()) {
            $product = $agent->getProducto();
            $newLine = $product->exists() ? $invoice->getNewProductLine($product->referencia) : $invoice->getNewLine();
            $newLine->cantidad = 1;
            $newLine->descripcion = Tools::lang()->trans('commission-settlement', ['%code%' => $this->idliquidacion]);
            $newLine->pvpunitario = $this->total;
            $newLine->save();

            $lines = [$newLine];
            if (Calculator::calculate($invoice, $lines, true)) {
                $this->idfactura = $invoice->idfactura;
                return $this->save();
            }

            $invoice->delete();
        }

        return false;
    }

    public function getAgent(): Agente
    {
        $agent = new Agente();
        $agent->loadFromCode($this->codagente);
        return $agent;
    }

    public function install(): string
    {
        // needed dependencies
        new Agente();
        new FacturaProveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idliquidacion';
    }

    public static function tableName(): string
    {
        return 'liquidacionescomisiones';
    }

    public function test(): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        $this->observaciones = Tools::noHtml($this->observaciones);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
