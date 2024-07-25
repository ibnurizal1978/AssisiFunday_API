@extends('master')
<!-- <link rel="stylesheet" href="assets/vendors/jquery-datatables/jquery.dataTables.min.css"> -->
<link rel="stylesheet" href="{{ asset('/vendors/jquery-datatables/jquery.dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('/vendors/fontawesome/all.min.css') }}">
<style>
    table.dataTable td{
        padding: 15px 8px;
    }
    .fontawesome-icons .the-icon svg {
        font-size: 24px;
    }
</style>

<body>
 
 
<div id="main">
    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>
            
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Leaderboard</h3>
                </div>
            </div>
        </div>

        <!-- Basic Tables start -->
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <table class="table" id="table1">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Score</th>
                                <th>Time</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $a)
                            <tr>
                                <td>{{ $a->full_name }}</td>
                                <td>{{ $a->email }}</td>
                                <td>{{ $a->score }}</td>
                                <td>{{ $a->time }}</td>
                                <td>{{ $a->created_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
        <!-- Basic Tables end -->
    </div>
</div>

<script src="{{ asset('/vendors/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('/vendors/jquery-datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/vendors/jquery-datatables/custom.jquery.dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('/vendors/fontawesome/all.min.js') }}"></script>
<script>
    // Jquery Datatable
    let jquery_datatable = $("#table1").DataTable()
</script>