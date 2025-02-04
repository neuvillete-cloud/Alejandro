<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historicos</title>
    <link rel="stylesheet" href="css/estilosHistoricos.css">
    <script src="js/jquery.min.js"></script>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <script src="js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.min.css" />
    <script type="text/javascript" src="js/datatables.min.js"></script>
</head>
<body>
<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>Solicitudes</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
    <div class="header-right">
        <div class="user-profile" id="profilePic">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario">
        </div>
        <div class="user-name" id="userNameHeader"></div>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="#">Ver Perfil</a>
            <a href="#" id="logout">Cerrar Sesión</a>
        </div>
    </div>
</header>
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="Solicitante.php" >Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="configuraciones.php">Configuraciones</a></li>
    </ul>
</nav>
<!-- Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Perfil del Usuario</h2>
        <div class="modal-body">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario" class="user-photo">
            <p><strong>Nombre:</strong> <span id="userName"></span></p>
            <p><strong>Número de Nómina:</strong> <span id="userNumNomina"></span></p>
            <p><strong>Área:</strong> <span id="userArea"></span></p>
        </div>
    </div>
</div>

<table id="example" tableexport-key="tabla" class="table display">
    <thead>
    <tr>
        <th>Nomina</th>
        <th>Nombre</th>
        <th>Puesto</th>
        <th>Puesto</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th>Filter..</th>
        <th>Filter..</th>
        <th>Filter..</th>
        <th>Filter..</th>
    </tr>
    </tfoot>
</table>


<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function () {

        $('#example tfoot th').each(function () {
            var title = $(this).text();
            $(this).html('<input type="text" placeholder="Filtrar..." class="form-control input-sm" size="3px" />');
        });
        var tabla = $('#example').DataTable({
            "responsive": true,
            "ajax": {
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoSoli.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "NumNomina" },
                { "data": "IdArea" },
                { "data": "Puesto" },
                { "data": "TipoContratacion" }
            ],
            "initComplete": function () {
                this.api().columns().every(function () {
                    var that = this;
                    $('input', this.footer()).on('keyup change', function () {
                        if (that.search() !== this.value) {
                            that
                                .search(this.value)
                                .draw();
                        }
                    });
                });
            },
            dom: 'lBfrtip',
            buttons: [
                {
                    extend: 'copyHtml5',
                    text: 'Copiar',
                    exportOptions: {
                        columns: [ 0, ':visible' ]
                    },
                    titleAttr: 'Copiar Texto',
                    className: 'btn btn-secondary'
                },
                {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [0, ':visible']
                    },
                    titleAttr: 'Exportar a Excel',
                    className: 'btn btn-success'
                },
                {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [0, ':visible']
                    },
                    titleAttr: 'Exportar a PDF',
                    className: 'btn btn-danger',
                    orientation: 'landscape',
                    pageSize: 'LEGAL'
                }
                /*
                {
                    text: 'Seleccione las columnas',
                    extend: 'colvis',
                    className: 'btn btn-info'
                }*/
            ],
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "loadingRecords": "Cargando...",
            "deferRender": true,
            "search": {
                "regex": true,
                "caseInsensitive": true,
            },
        });


        $('#min').datepicker({ onSelect: function () { table.draw(); }, changeMonth: true, changeYear: true });
        $('#max').datepicker({ onSelect: function () { table.draw(); }, changeMonth: true, changeYear: true });
        //  var table = $('#example').DataTable();

        $('#min, #max').change(function () {
            table.draw();
        });
        //
    });
</script>
</body>
</html>