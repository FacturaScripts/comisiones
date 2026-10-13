<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Comisiones\Controller;

use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\Template\Controller;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Informe con las comisiones pendientes de liquidar y las últimas liquidaciones
 * del agente vinculado al usuario. Los administradores pueden elegir el agente.
 *
 * @author Esteban Sánchez Martínez <Esteban@factura.city>
 */
class MisComisiones extends Controller
{
    /** @var int Número máximo de liquidaciones que se muestran. */
    const MAX_LIQUIDACIONES = 20;

    /** @var array Lista de agentes seleccionables [codagente => nombre]. Solo para administradores. */
    public $agentes = [];

    /** @var string Código del agente cuyas comisiones se muestran. */
    public $codagente = '';

    /** @var FacturaCliente[] Facturas de cliente con comisión que todavía no están en ninguna liquidación. */
    public $facturas = [];

    /** @var array Últimas liquidaciones del agente, con su factura de proveedor generada si la hay y sus facturas de cliente. */
    public $liquidaciones = [];

    /** @var bool Indica si no hay agente del que mostrar datos (usuario sin agente vinculado). */
    public $sinAgente = false;

    /** @var float Suma de las comisiones de las facturas pendientes de liquidar. */
    public $totalPendiente = 0.0;

    /**
     * Devuelve los datos de la página para el menú de informes.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'commissions';
        $data['icon'] = 'fa-solid fa-percent';
        return $data;
    }

    /**
     * Ejecuta el controlador: determina el agente y carga el informe.
     */
    public function run(): void
    {
        parent::run();

        $this->codagente = $this->resolveAgent();
        if (empty($this->codagente)) {
            $this->sinAgente = true;
        } else {
            $this->loadPendingInvoices();
            $this->loadSettlements();
        }

        $this->view('MisComisiones.html.twig');
    }

    /**
     * Carga las facturas de cliente del agente con comisión y sin liquidar,
     * y calcula el total pendiente a partir de esas mismas facturas.
     */
    protected function loadPendingInvoices(): void
    {
        $where = [
            Where::eq('codagente', $this->codagente),
            Where::isNull('idliquidacion'),
            Where::notEq('totalcomision', 0)
        ];
        $this->facturas = FacturaCliente::all($where, ['fecha' => 'DESC', 'idfactura' => 'DESC']);

        foreach ($this->facturas as $factura) {
            $this->totalPendiente += $factura->totalcomision;
        }
    }

    /**
     * Carga las últimas liquidaciones del agente, las más recientes primero,
     * junto con las facturas de cliente que incluye cada una.
     */
    protected function loadSettlements(): void
    {
        $where = [Where::eq('codagente', $this->codagente)];
        $order = ['fecha' => 'DESC', 'idliquidacion' => 'DESC'];
        $liquidaciones = LiquidacionComision::all($where, $order, 0, static::MAX_LIQUIDACIONES);
        if (empty($liquidaciones)) {
            return;
        }

        // una sola consulta para las facturas de todas las liquidaciones
        $ids = array_map(fn($liquidacion) => $liquidacion->idliquidacion, $liquidaciones);
        $facturasPorLiquidacion = [];
        $whereFacturas = [Where::in('idliquidacion', $ids)];
        foreach (FacturaCliente::all($whereFacturas, ['fecha' => 'DESC', 'idfactura' => 'DESC']) as $facturaCliente) {
            $facturasPorLiquidacion[$facturaCliente->idliquidacion][] = $facturaCliente;
        }

        foreach ($liquidaciones as $liquidacion) {
            $factura = new FacturaProveedor();
            $tieneFactura = !empty($liquidacion->idfactura) && $factura->load($liquidacion->idfactura);

            $this->liquidaciones[] = [
                'liquidacion' => $liquidacion,
                'factura' => $tieneFactura ? $factura : null,
                'facturas' => $facturasPorLiquidacion[$liquidacion->idliquidacion] ?? []
            ];
        }
    }

    /**
     * Devuelve el agente del que se muestran las comisiones. Un usuario normal
     * solo ve el suyo; un administrador puede elegir otro con el parámetro codagente.
     * Devuelve cadena vacía si no hay agente que mostrar.
     *
     * @return string
     */
    protected function resolveAgent(): string
    {
        if (empty($this->user->admin)) {
            return (string)$this->user->codagente;
        }

        foreach (Agentes::all() as $agente) {
            $this->agentes[$agente->codagente] = $agente->nombre;
        }

        $solicitado = (string)$this->request()->queryOrInput('codagente', '');
        if (isset($this->agentes[$solicitado])) {
            return $solicitado;
        }

        // por defecto, el agente vinculado al administrador si lo tiene
        $propio = (string)$this->user->codagente;
        return isset($this->agentes[$propio]) ? $propio : '';
    }
}
