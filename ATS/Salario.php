<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grammer Automotive - Calculadora de Sueldo</title>
    <link rel="stylesheet" href="css/loginAts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .calculadora-sueldo {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: #063962;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            background: #063962;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #052b4e;
        }
        #detalleRetenciones {
            text-align: left;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="indexAts.php">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="#">Inclusión y diversidad</a>
            <a href="loginATS.php">Inicio de sesión</a>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Calculadora de ISR</h1>
    <img src="imagenes/iniciar-sesion.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="section-calculadora">
    <section class="calculadora-sueldo">
        <h2>Calcula tu sueldo Neto o Bruto</h2>
        <p>Selecciona el tipo de cálculo e ingresa el monto correspondiente.</p>

        <form id="formCalculadora">
            <div class="form-group">
                <label>Periodo de pago:</label>
                <select id="periodoPago">
                    <option value="mensual">Mensual</option>
                    <option value="quincenal">Quincenal</option>
                    <option value="semanal">Semanal</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de cálculo:</label>
                <label><input type="radio" name="tipoCalculo" value="brutoANeto" checked> Bruto a Neto</label>
                <label><input type="radio" name="tipoCalculo" value="netoABruto"> Neto a Bruto</label>
            </div>

            <div class="form-group">
                <label>Monto:</label>
                <input type="number" id="montoBruto" placeholder="$ Monto" required>
            </div>

            <button type="submit">Calcular</button>
        </form>

        <section id="resultadoCalculadora" style="display:none;">
            <h3>Resultados:</h3>
            <div id="detalleRetenciones"></div>
            <canvas id="graficoDeducciones" width="300" height="300"></canvas>
        </section>
    </section>
</section>


<script>
    document.getElementById('formCalculadora').addEventListener('submit', async function(e) {
        e.preventDefault();

        const montoIngresado = parseFloat(document.getElementById('montoBruto').value);
        const periodo = document.getElementById('periodoPago').value;
        const tipoCalculo = document.querySelector('input[name="tipoCalculo"]:checked').value;

        if (isNaN(montoIngresado) || montoIngresado <= 0) {
            alert("Por favor ingresa un monto válido.");
            return;
        }

        const response = await fetch('api/calculadora.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                monto: montoIngresado,
                periodo: periodo,
                tipo: tipoCalculo
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            const resultado = data.data;

            document.getElementById('detalleRetenciones').innerHTML = `
            <p><strong>Periodo:</strong> ${resultado.periodo}</p>
            <p><strong>Sueldo Bruto:</strong> $${resultado.sueldo_bruto}</p>
            <p><strong>ISR:</strong> -$${resultado.ISR}</p>
            <p><strong>Subsidio al Empleo:</strong> +$${resultado.Subsidio}</p>
            <p><strong>Sueldo Neto:</strong> $${resultado.sueldo_neto}</p>
        `;

            document.getElementById('resultadoCalculadora').style.display = 'block';

            renderGraficoDeducciones(resultado.sueldo_bruto, resultado.ISR, resultado.Subsidio, resultado.sueldo_neto);
        } else {
            alert("Error: " + data.message);
        }
    });

    function renderGraficoDeducciones(bruto, isr, subsidio, neto) {
        const ctx = document.getElementById('graficoDeducciones').getContext('2d');
        const deducciones = isr - subsidio;
        const deduccionReal = deducciones > 0 ? deducciones : 0;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Deducciones', 'Sueldo Neto'],
                datasets: [{
                    data: [deduccionReal, neto],
                    backgroundColor: ['#FF6384', '#36A2EB']
                }]
            },
            options: {
                plugins: {
                    legend: {position: 'bottom'}
                }
            }
        });
    }
</script>

</body>
</html>
