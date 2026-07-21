@extends('layouts.app')

@section('title', 'Pronunciation Learning')

@section('page-title', 'Learn Pronunciation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pronunciation.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    <div class="pronunciation-card">

        <div class="pronunciation-header">
            <h4>Type Hanzi (汉字) here:</h4>
            <p class="pronunciation-subtitle">
                Enter Chinese characters below to hear the pronunciation and see the pinyin.
            </p>
        </div>

        <textarea
            id="inputText"
            class="form-control pronunciation-textarea"
            rows="5"
            placeholder="e.g. 你好，世界！"></textarea>

        <button id="generate"
            class="btn pronunciation-btn mt-3">
            <i class="bi bi-soundwave"></i>
            Generate
        </button>

        <hr class="pronunciation-divider">

        <audio
            id="player"
            controls
            class="pronunciation-audio">
        </audio>

        <div class="pronunciation-result">

            <h4>Pinyin (拼音) result:</h4>

            <textarea
                id="pinyinResult"
                class="form-control pronunciation-textarea pronunciation-textarea-result"
                rows="5" disabled></textarea>

        </div>

    </div>

</div>

@push('scripts')
<script>
    const textarea = document.getElementById('inputText');
    const pinyinText = document.getElementById('pinyinResult');
    const audio = document.getElementById('player');

    document.getElementById('generate').onclick = function() {

        fetch('/tts/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    text: textarea.value
                })
            })
            .then(r => r.json())
            .then(data => {
                pinyinText.innerHTML = data.pinyin;
                if (data.type === 'stream') {
                    audio.src = "data:audio/mpeg;base64," + data.audio;
                } else {
                    audio.src = data.audio;
                }
                audio.play();
            });
    }
</script>
@endpush

@endsection