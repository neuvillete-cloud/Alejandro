fetch('dao/manejoDashboard.php') // Asegúrate de que esta ruta sea correcta
    .then(response => response.json())
    .then(data => {
        const meses = data.meses;  // Ahora 'meses' tendrá los nombres de los meses
        const reportesTotales = data.totales;  // Array de reportes totales por mes
        const reportesResueltos = data.resueltos;  // Array de reportes resueltos por mes

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

        // Gráfico de reportes resueltos
        const ctxResueltos = document.getElementById('resueltosChart').getContext('2d');
        const resueltosChart = new Chart(ctxResueltos, {
            type: 'line',
            data: {
                labels: meses,  // Los meses ahora son nombres como "Enero", "Febrero", etc.
                datasets: [{
                    label: 'Reportes Resueltos',
                    data: reportesResueltos,
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
