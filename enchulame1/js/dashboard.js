fetch('dao.manejoDashboard.php') // Cambia esta ruta por la correcta
    .then(response => response.json())
    .then(data => {
        // Extraemos los datos para las gráficas
        const mesesRegistro = data.mesesRegistro;  // Meses de los reportes registrados
        const reportesTotales = data.totales;  // Array de reportes totales por mes
        const mesesFinalizados = data.mesesFinalizados;  // Meses de los reportes finalizados
        const reportesFinalizados = data.finalizados;  // Array de reportes finalizados por mes

        // Gráfico combinado de reportes registrados y finalizados
        const ctxReporte = document.getElementById('reporteChart').getContext('2d');
        const reporteChart = new Chart(ctxReporte, {
            type: 'bar',
            data: {
                labels: mesesRegistro.map(m => new Date(0, m - 1).toLocaleString('default', { month: 'long' })),  // Convertir el número del mes en nombre
                datasets: [
                    {
                        label: 'Reportes Registrados',
                        data: reportesTotales,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Reportes Finalizados',
                        data: reportesFinalizados,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
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
