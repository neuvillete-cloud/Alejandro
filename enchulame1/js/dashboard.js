fetch('dao/manejoDashboard.php') // Asegúrate de que esta ruta sea correcta
    .then(response => response.json())
    .then(data => {
        const meses = data.meses;  // Los meses (por ejemplo: "Enero", "Febrero", etc.)
        const reportesTotales = data.totales;  // Reportes registrados
        const reportesFinalizados = data.finalizados;  // Reportes finalizados

        // Gráfico de reportes registrados
        const ctxReporte = document.getElementById('reporteChart').getContext('2d');
        const reporteChart = new Chart(ctxReporte, {
            type: 'bar',
            data: {
                labels: meses,  // Los meses ahora son nombres como "Enero", "Febrero", etc.
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
                labels: meses,  // Los meses ahora son nombres como "Enero", "Febrero", etc.
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
