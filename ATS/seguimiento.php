<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento - Porcentajes</title>
    <link rel="stylesheet" href="css/estilosSeguimiento.css">
</head>
<body>

<h1>Seguimiento de Progreso</h1>
<div class="progress-container">
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const percentages = [75, 50, 90]; // Porcentajes a llenar
        const circles = document.querySelectorAll(".circle");

        circles.forEach((circle, index) => {
            const progressCircle = circle.querySelector(".progress");
            const percentageText = circle.querySelector(".percentage");

            const radius = progressCircle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;

            progressCircle.style.strokeDasharray = `${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;

            let progress = 0;
            const target = percentages[index];

            const interval = setInterval(() => {
                if (progress <= target) {
                    const offset = circumference - (progress / 100) * circumference;
                    progressCircle.style.strokeDashoffset = offset;
                    percentageText.textContent = `${progress}%`;
                    progress++;
                } else {
                    clearInterval(interval);
                }
            }, 20); // Velocidad de animación
        });
    });
</script>

</body>
</html>
