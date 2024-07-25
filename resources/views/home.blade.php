@extends('master')
@section('title', 'Magicelan')
<div class="content-page">
    <div class="content">
        <!--container-->
        <div class="container-fluid">
            <div class="row page-title">
                <div class="col-md-12">
                    <br/>
                    <h4 class="mb-1 mt-0 text-center">{{Session::get('username') }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>