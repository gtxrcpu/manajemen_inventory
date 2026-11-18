@extends('layouts.dashboard')

@php($pageTitle = 'Laporan Management - Technolife')

@section('mobile-content')
    @include('management._dashboard-content', ['mobile' => true])
@endsection

@section('desktop-content')
    @include('management._dashboard-content', ['mobile' => false])
@endsection
