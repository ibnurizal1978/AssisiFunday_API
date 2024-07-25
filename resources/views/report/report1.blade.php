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
                    <h3>MAGICLEAN FORTUNE TOWN CNY'22 CAMPAIGN</h3>
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
                                <th>No.</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Checked to consent receiving marketing materials via email</th>
                                <th>Best score</th>
                                <th>Score</th>
                                <th>eDM sent</th>
                                <th>Total time in game</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $a)
                            <tr>
                                <td>a</td>
                                <td>{{ $a->created_at }}</td>
                                <td>{{ $a->time }}</td>
                                <td>{{ $a->full_name }}</td>
                                <td>{{ $a->email }}</td>
                                <td>{{ $a->score }}</td>
                                <td>{{ $a->score }}</td>
                                <td>{{ $a->email_sent_status }}</td>
                                <td>{{ $a->time }}</td>                           
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