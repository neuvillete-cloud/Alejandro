<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document</title>
    <link rel="stylesheet" href="css/estilosSolicitante.css">
    <link rel="stylesheet" href="css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
</head>
<body>

<table class="table datatables" id="dataTable-1">
    <thead>
    <tr>
        <th>Folio</th>
        <th>Número de Parte</th>
        <th>Primer Conteo</th>
        <th>Segundo Conteo</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th>Folio</th>
        <th>Número de Parte</th>
        <th>Primer Conteo</th>
        <th>Segundo Conteo</th>
    </tr>
    </tfoot>
    <tbody>

    </tbody>
</table>


<script>
    $.ajax({
        url: 'https://grammermx.com/AleTest/ATS/dao/daoSoli.php', // Reemplaza esto con la URL de tus datos
        dataType: 'json',
        success: function(data) {
            var table = $('#dataTable-1').DataTable({
                data: data.data,
                columns: [
                    { data: 'NumNomina' },
                    { data: 'IdArea' },
                    { data: 'Puesto' },
                    { data: 'TipoContratacion' }
                ],
                autoWidth: true,
                "lengthMenu": [
                    [16, 32, 64, -1],
                    [16, 32, 64, "All"]
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'btn btn-sm copyButton'
                    },
                    {
                        extend: 'csv',
                        className: 'btn btn-sm csvButton'
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-sm excelButton'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm pdfButton'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm printButton'
                    }
                ],
                initComplete: function () {
                    this.api().columns().every( function () {
                        var column = this;
                        var input = document.createElement("input");
                        input.className = 'form-control form-control-sm';
                        $(input).appendTo($(column.footer()).empty())
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    });
                }
            });
        }
    });
</script>
</body>
</html>