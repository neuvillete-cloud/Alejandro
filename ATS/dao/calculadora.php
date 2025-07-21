<?php
header('Content-Type: application/json');

// Tabla del ISR con datos necesarios
function obtenerTarifaISR($baseGravable) {
    $tarifa = [
        [0.01, 746.04, 0.00, 0.0192],
        [746.05, 6332.05, 14.32, 0.0640],
        [6332.06, 11128.01, 371.83, 0.1088],
        [11128.02, 12935.82, 893.63, 0.16],
        [12935.83, 15487.71, 1182.88, 0.1792],
        [15487.72, 31236.49, 1640.18, 0.2136],
        [31236.50, 49233.00, 5004.12, 0.2352],
        [49233.01, 93993.90, 9236.89, 0.3],
        [93993.91, 125325.20, 22665.17, 0.32],
        [125325.21, 375975.61, 32691.18, 0.34],
        [375975.62, PHP_FLOAT_MAX, 117912.32, 0.35]
    ];

    foreach ($tarifa as $rango) {
        [$limInf, $limSup, $cuotaFija, $porcentaje] = $rango;
        if ($baseGravable >= $limInf && $baseGravable <= $limSup) {
            $excedente = $baseGravable - $limInf;
            $impuestoMarginal = $excedente * $porcentaje;
            $ISR = round($cuotaFija + $impuestoMarginal, 2);
            return [
                'limite_inferior' => $limInf,
                'excedente' => round($excedente, 2),
                'porcentaje' => $porcentaje,
                'cuota_fija' => $cuotaFija,
                'impuesto_marginal' => round($impuestoMarginal, 2),
                'ISR' => $ISR
            ];
        }
    }
    return [];
}

// Subsidio al empleo (simplificado)
function obtenerSubsidio($baseGravable) {
    return ($baseGravable <= 10171) ? 475.00 : 0.00;
}

// IMSS estimado
function calcularIMSS($baseGravable) {
    return round($baseGravable * 0.025, 2); // 2.5% estimado
}

// Entrada JSON
$input = json_decode(file_get_contents('php://input'), true);
$periodo = strtolower($input['periodo'] ?? 'mensual');
$monto = floatval($input['monto'] ?? 0);
$tipoCalculo = $input['tipo'] ?? 'brutoANeto';

if ($monto <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Monto inválido']);
    exit;
}

// Ajustes de periodo
function ajustarAMensual($periodo, $valor) {
    switch ($periodo) {
        case 'quincenal': return $valor * 2;
        case 'semanal': return $valor * 4.3333;
        default: return $valor;
    }
}

function ajustarADeseado($periodo, $valor) {
    switch ($periodo) {
        case 'quincenal': return round($valor / 2, 2);
        case 'semanal': return round($valor / 4.3333, 2);
        default: return $valor;
    }
}

// =========================
// BRUTO A NETO
// =========================
if ($tipoCalculo === 'brutoANeto') {
    $baseGravable = ajustarAMensual($periodo, $monto);
    $detalleISR = obtenerTarifaISR($baseGravable);
    $subsidioMensual = obtenerSubsidio($baseGravable);
    $imssMensual = calcularIMSS($baseGravable);

    $bruto = $monto;
    $isr = ajustarADeseado($periodo, $detalleISR['ISR']);
    $subsidio = ajustarADeseado($periodo, $subsidioMensual);
    $imss = ajustarADeseado($periodo, $imssMensual);
    $neto = round($bruto - $isr - $imss + $subsidio, 2);

// =========================
// NETO A BRUTO (Aproximado)
// =========================
} elseif ($tipoCalculo === 'netoABruto') {
    $brutoAproxMensual = ajustarAMensual($periodo, $monto);
    $brutoEstimado = $brutoAproxMensual;

    do {
        $detalleISR = obtenerTarifaISR($brutoEstimado);
        $subsidioMensual = obtenerSubsidio($brutoEstimado);
        $imssMensual = calcularIMSS($brutoEstimado);

        $netoEstimado = $brutoEstimado - $detalleISR['ISR'] - $imssMensual + $subsidioMensual;

        if ($netoEstimado < $brutoAproxMensual) {
            $brutoEstimado += 1;
        } else {
            $brutoEstimado -= 0.5;
        }

    } while (abs($netoEstimado - $brutoAproxMensual) > 1);

    $bruto = ajustarADeseado($periodo, $brutoEstimado);
    $isr = ajustarADeseado($periodo, $detalleISR['ISR']);
    $subsidio = ajustarADeseado($periodo, $subsidioMensual);
    $imss = ajustarADeseado($periodo, $imssMensual);
    $neto = $monto;

// =========================
// TIPO INVÁLIDO
// =========================
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tipo de cálculo inválido']);
    exit;
}

// =========================
// RESPUESTA JSON COMPLETA
// =========================
echo json_encode([
    'status' => 'success',
    'data' => [
        'periodo' => ucfirst($periodo),
        'sueldo_bruto' => number_format($bruto, 2),
        'limite_inferior' => number_format($detalleISR['limite_inferior'], 2),
        'excedente' => number_format($detalleISR['excedente'], 2),
        'porcentaje' => ($detalleISR['porcentaje'] * 100) . '%',
        'cuota_fija' => number_format($detalleISR['cuota_fija'], 2),
        'impuesto_marginal' => number_format($detalleISR['impuesto_marginal'], 2),
        'ISR' => number_format($isr, 2),
        'IMSS' => number_format($imss, 2),
        'Subsidio' => number_format($subsidio, 2),
        'sueldo_neto' => number_format($neto, 2)
    ]
]);
