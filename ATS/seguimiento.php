<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento - Porcentajes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f4f4f4;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .progress-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .circle {
            position: relative;
            width: 120px;
            height: 120px;
        }

        svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        circle {
            fill: none;
            stroke: #e6e6e6;
            stroke-width: 10;
        }

        circle.progress {
            stroke: #3498db;
            stroke-dasharray: 283; /* Circunferencia del círculo */
            stroke-dashoffset: 283;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease-in-out;
        }

        .percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
    </style>
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
