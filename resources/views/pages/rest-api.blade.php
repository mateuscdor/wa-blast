@extends('layouts.app')

@section('title')
    REST API
@endsection

@push('head')

    <script src="//cdnjs.cloudflare.com/ajax/libs/hi`, ghlight.js/11.7.0/highlight.min.js"></script>
    <style>
        /* Dracula Theme v1.2.5
 *
 * https://github.com/dracula/highlightjs
 *
 * Copyright 2016-present, All rights reserved
 *
 * Code licensed under the MIT license
 *
 * @author Denis Ciccale <dciccale@gmail.com>
 * @author Zeno Rocha <hi@zenorocha.com>
 */

        .hljs {
            display: block;
            overflow-x: auto;
            padding: 0.5em;
            background: #282a36;
        }

        .hljs-built_in,
        .hljs-selector-tag,
        .hljs-section,
        .hljs-link {
            color: #8be9fd;
        }

        .hljs-keyword {
            color: #ff79c6;
        }

        .hljs,
        .hljs-subst {
            color: #f8f8f2;
        }

        .hljs-title,
        .hljs-attr,
        .hljs-meta-keyword {
            font-style: italic;
            color: #50fa7b;
        }

        .hljs-string,
        .hljs-meta,
        .hljs-name,
        .hljs-type,
        .hljs-symbol,
        .hljs-bullet,
        .hljs-addition,
        .hljs-variable,
        .hljs-template-tag,
        .hljs-template-variable {
            color: #f1fa8c;
        }

        .hljs-comment,
        .hljs-quote,
        .hljs-deletion {
            color: #6272a4;
        }

        .hljs-keyword,
        .hljs-selector-tag,
        .hljs-literal,
        .hljs-title,
        .hljs-section,
        .hljs-doctag,
        .hljs-type,
        .hljs-name,
        .hljs-strong {
            font-weight: bold;
        }

        .hljs-literal,
        .hljs-number {
            color: #bd93f9;
        }

        .hljs-emphasis {
            font-style: italic;
        }
    </style>
    <style>
        pre code {
            border-radius: 8px !important;
        }
    </style>
@endpush

@section('content')
    <h2 class="my-5">Rest API</h2>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Method</th>
                        <th scope="col">POST & GET ( All support )</th>
                    </tr>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">JSON</th>
                    </tr>
                    <tr>
                        <th scope="col">RESPONSE</th>
                        <th scope="col">{  status : boolean , msg : 'text'  }  (JSON)</th>
                    </tr>
                    </thead>

                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <p class="card-description">Rest Api </p>
                    <div class="example-container">
                        <div class="example-content">
                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#textMessage" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Text Message</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#imageMessage" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Media Message</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#buttonMessage" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Button Message </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#templateMessage" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Template Message </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#listMessage" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">List Message </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#generateQr" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Generate Qr</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#webhook" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Webhook</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade active show" id="textMessage" role=" tabpanel" aria-labelledby="pills-home-tab">
                                    @include('components.rest-api.rest-tab-text')
                                </div>
                                <div class="tab-pane fade" id="imageMessage" role="tabpanel" aria-labelledby="pills-profile-tab">
                                    @include('components.rest-api.rest-tab-media')
                                </div>
                                <div class="tab-pane fade" id="buttonMessage" role="tabpanel" aria-labelledby="pills-contact-tab">
                                    @include('components.rest-api.rest-tab-button')
                                </div>

                                <div class="tab-pane fade" id="templateMessage" role="tabpanel" aria-labelledby="pills-contact-tab">
                                    @include('components.rest-api.rest-tab-template')
                                </div>

                                {{-- List Message --}}
                                <div class="tab-pane fade" id="listMessage" role="tabpanel" aria-labelledby="pills-contact-tab">
                                    @include('components.rest-api.rest-tab-list')
                                </div>

                                {{-- Generate qr --}}
                                <div class="tab-pane fade" id="generateQr" role="tabpanel" aria-labelledby="pills-contact-tab">
                                    @include('components.rest-api.rest-tab-qr')
                                </div>
                                {{-- Webhook--}}
                                <div class="tab-pane fade" id="webhook" role="tabpanel" aria-labelledby="pills-contact-tab">
                                    @include('components.rest-api.rest-tab-webhook')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>hljs.highlightAll();</script>
@endpush
