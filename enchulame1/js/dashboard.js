// Función para obtener los datos del servidor
fetch('dao/manejoDashboard.php') // Cambia esta ruta por la correcta
    .then(response => response.json())
    .then(data => {
        // Extraemos los datos para las gráficas
        const mesesRegistro = data.mesesRegistro;  // Meses de los reportes registrados
        const reportesTotales = data.totales;  // Array de reportes totales por mes
        const mesesFinalizados = data.mesesFinalizados;  // Meses de los reportes finalizados
        const reportesFinalizados = data.finalizados;  // Array de reportes finalizados por mes

        // Gráfico de reportes registrados
        const ctxReporte = document.getElementById('reporteChart').getContext('2d');
        const reporteChart = new Chart(ctxReporte, {
            type: 'bar',
            data: {
                labels: mesesRegistro.map(m => new Date(0, m - 1).toLocaleString('default', { month: 'long' })), // Convertir el número del mes en nombre
                datasets: [{
                    label: 'Reportes Registrados',
                    data: reportesTotales,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de reportes finalizados
        const ctxFinalizados = document.getElementById('finalizadosChart').getContext('2d');
        const finalizadosChart = new Chart(ctxFinalizados, {
            type: 'line',
            data: {
                labels: mesesFinalizados.map(m => new Date(0, m - 1).toLocaleString('default', { month: 'long' })),  // Convertir mes a nombre
                datasets: [{
                    label: 'Reportes Finalizados',
                    data: reportesFinalizados,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    })
    .catch(error => console.error('Error al obtener los datos:', error));
