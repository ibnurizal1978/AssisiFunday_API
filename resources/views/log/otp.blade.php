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
                    <h3>OTP Log</h3>
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
                                <th>Sent Status</th>
                                <th>OTP Code</th>
                                <th>Used Status</th>
                                <th>Created at</th>
                                <th>Updated at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $a)
                            <tr>
                                <td>{{ $a->email }}</td>
                                <td>{{ $a->email_sent_status }}</td>
                                <td>{{ $a->otp_code }}</td>
                                <td>@if($a->otp_used_status == 1)<span class="badge bg-success">Used</span>@else<span class="badge bg-danger">Unused</span>@endif</td>
                                <td>{{ date('d-m-Y H:i:s', strtotime($a->created_at)) }}</td>
                                <td>{{ date('d-m-Y H:i:s', strtotime($a->updated_at)) }}</td>
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