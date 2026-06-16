@extends('layouts.admin')
@section('title', 'Master Data')
@section('content')
<h2>Master Data</h2>
<div class="row g-3">
    <div class="col-md-3"><a href="{{ route('admin.master.subjects') }}" class="card border-0 shadow-sm text-decoration-none text-dark p-3"><h5>Mata Pelajaran</h5><h2>{{ $stats['subjects'] }}</h2></a></div>
    <div class="col-md-3"><a href="{{ route('admin.master.classrooms') }}" class="card border-0 shadow-sm text-decoration-none text-dark p-3"><h5>Kelas</h5><h2>{{ $stats['classrooms'] }}</h2></a></div>
    <div class="col-md-3"><a href="{{ route('admin.master.academic-years') }}" class="card border-0 shadow-sm text-decoration-none text-dark p-3"><h5>Tahun Ajaran</h5><h2>{{ $stats['academic_years'] }}</h2></a></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><h5>Siswa</h5><h2>{{ $stats['students'] }}</h2></div></div>
</div>
@endsection
