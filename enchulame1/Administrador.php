<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Dashboard</title>
    <link rel="stylesheet" href="css/estilosAdministrador.css"> <!-- Vincula tu archivo CSS -->
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>eProduct</h2>
        </div>
        <nav class="menu">
            <a href="#" class="menu-item active">Dashboard</a>
            <a href="#" class="menu-item">Order</a>
            <a href="#" class="menu-item">Statistic</a>
            <a href="#" class="menu-item">Product</a>
            <a href="#" class="menu-item">Stock</a>
            <a href="#" class="menu-item">Offer</a>
        </nav>
        <div class="social-links">
            <a href="#">Facebook</a>
            <a href="#">Twitter</a>
            <a href="#">Google</a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="header">
            <h1>Order</h1>
            <div class="date-filter">
                <input type="date">
                <input type="date">
            </div>
        </header>
        <section class="order-list">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Date</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <!-- Aquí puedes añadir filas de ejemplo -->
                <tr>
                    <td>#2853</td>
                    <td>John McCormick</td>
                    <td>1098 Weissman Street, CALAMA, AK</td>
                    <td>01 Aug 2020</td>
                    <td>$35.00</td>
                    <td><span class="status dispatch">Dispatch</span></td>
                    <td><button class="action-btn">...</button></td>
                </tr>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
