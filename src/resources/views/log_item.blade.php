@extends(backpack_view('layouts.top_left'))

@php
    $breadcrumbs = [
      trans('backpack::crud.admin') => backpack_url('dashboard'),
      trans('romansev::logmanager.log_manager') => backpack_url('log'),
      trans('romansev::logmanager.preview') => false,
    ];
@endphp

@section('header')
    <section class="container-fluid">
        <h2>11
            {{ trans('romansev::logmanager.log_manager') }}<small>{{ trans('romansev::logmanager.file_name') }}: <i>{{ $file_name }}</i></small>
            <small><a href="{{ backpack_url('log') }}" class="hidden-print font-sm"><i class="la la-angle-double-left"></i> {{ trans('romansev::logmanager.back_to_all_logs') }}</a></small>
        </h2>
    </section>
@endsection

@section('content')
    <div id="accordion" role="tablist" aria-multiselectable="true">
        @forelse($logs as $key => $log)
            <div class="card mb-0 pb-0">
                <div class="card-header bg-{{ $log['level_class'] }}" role="tab" id="heading{{ $key }}">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse{{ $key }}" aria-expanded="true" aria-controls="collapse{{ $key }}" class="text-white">
                        <i class="la la-{{ $log['level_img'] }}"></i>
                        <span>[{{ $log['date'] }}]</span>
                        {{ Str::limit($log['text'], 150) }}
                    </a>
                </div>
                <div id="collapse{{ $key }}" class="panel-collapse collapse p-3" role="tabpanel" aria-labelledby="heading{{ $key }}">
                    <div class="panel-body">
                        {{--            <p>{{$log['text']}}</p>--}}
                        <div id="jsoneditor-{{ $key }}" style="height: 400px;"></div>

                        <pre><code class="php">
              {{ trim($log['stack']) }}
            </code></pre>
                    </div>
                </div>
            </div>
        @empty
            <h3 class="text-center">No Logs to display.</h3>
        @endforelse
    </div>

@endsection

@section('after_scripts')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/styles/default.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
@endsection

@push('after_scripts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.1.1/jsoneditor.min.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.1.1/jsoneditor.min.js"></script>
    <script>
        let container, jsonString, options, editor;
    </script>
@endpush

@forelse($logs as $key => $log)
    @push('after_scripts')
        <script>
            container = document.getElementById('jsoneditor-{{ $key }}');
            jsonString = @json($log['text']);

            options = {
                onChange: function() {
                    const hiddenField = document.getElementById('{{ $key }}');
                    hiddenField.value = window['editor_{{ $key }}'].getText();
                },
                modes: ['form', 'tree', 'code'],
            };

            window['editor_{{ $key }}'] = new JSONEditor(container, options, JSON.parse(jsonString));
            document.getElementById('{{ $key }}').value = window['editor_{{ $key }}'].getText();
        </script>
    @endpush
@empty
@endforelse