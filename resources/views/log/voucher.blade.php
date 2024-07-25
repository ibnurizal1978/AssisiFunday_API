@extends('master')
 
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
                    <h3>Email Voucher Log</h3>
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
                                <th>Email</th>
                                <th>Sent at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $a)
                            <tr>
                                <td>{{ $a->email }}</td>
                                <td>{{ date('d-m-Y H:i:s', strtotime($a->created_at)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{$data->links()}}
                </div>
            </div>

        </section>
        <!-- Basic Tables end -->
    </div>
</div>