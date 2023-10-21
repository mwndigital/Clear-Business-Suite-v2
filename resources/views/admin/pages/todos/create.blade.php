@extends('layouts.admin')
<x-admin.page-top
    pageTitle="Create Todo"
    pageStyles=""
    pageScripts=""
/>
@section('content')
    <x-admin.page-hero
        title="Create Todo"
        displayButton="yes"
        buttonContent="All My Todo"
        buttonLink="{{ route('admin.todos.index') }}"
    />

    {{ Breadcrumbs::render('admin-todos-create') }}

    <x-admin.errors/>

    <section class="pageMain">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <form action="{{ route('admin.todos.store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <label for="">Title *</label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}" required>
                                <x.form-errors fieldName="title"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">Content *</label>
                                <textarea name="description" id="description" cols="30" rows="10" class="tinyEditor" required>{{ old('description') }}</textarea>
                                <x.form-errors fieldName="description"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Assigned to *</label>
                                <select name="user_id" id="user_id" required>
                                    @foreach($staff as $staff)
                                        <option value="{{ $staff->id }}" @if($staff->id == Auth::user()->id)selected @endif>{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                    @endforeach
                                </select>
                                <x.form-errors fieldName="user_id"/>
                            </div>
                            <div class="col-md-4">
                                <label for="">Status</label>
                                <select name="status" id="status">
                                    <option value="new">New</option>
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In-progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <x.form-errors fieldName="status"/>
                            </div>
                            <div class="col-md-4">
                                <label for="">Due Date</label>
                                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}">
                                <x.form-errors fieldName="due_date"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-lg darkBlueBtn">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

@endsection
