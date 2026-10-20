<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Overtrue\Pinyin\Pinyin;
use Afaya\EdgeTTS\Service\EdgeTTS;

class TTSController extends Controller
{
    const STREAM_LIMIT = 200;      // Hanzi; above this the audio is served as a temp file
    const TEMP_DIR = 'tts-temp';
    const TTL_MINUTES = 30;

    public function generate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = trim($request->text);

        $pinyin = implode(' ', (new Pinyin())->sentence($text)->toArray());
        $pinyin = preg_replace('/\s+([，。！？；：])/u', '$1', $pinyin);

        $hanziCount = preg_match_all('/\p{Han}/u', $text);

        $tts = new EdgeTTS();
        $tts->synthesize($text, 'zh-CN-XiaoxiaoNeural');
        $audio = $tts->toRaw();

        $payload = [
            'hanzi' => $text,
            'pinyin' => $pinyin,
            'hanzi_count' => $hanziCount,
        ];

        // SHORT TEXT: Base64 in the JSON, nothing touches the disk
        if ($hanziCount <= self::STREAM_LIMIT) {
            return response()->json($payload + [
                'audio' => base64_encode($audio),
                'type' => 'stream',
            ]);
        }

        $id = (string) Str::uuid();
        Storage::disk('local')->put(self::TEMP_DIR . "/{$id}.mp3", $audio);

        return response()->json($payload + [
            'audio' => URL::temporarySignedRoute(
                'tts.audio',
                now()->addMinutes(self::TTL_MINUTES),
                ['id' => $id]
            ),
            'type' => 'file',
        ]);
    }

    public function audio(string $id)
    {
        abort_unless(Str::isUuid($id), 404);

        $path = self::TEMP_DIR . "/{$id}.mp3";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'audio/mpeg',
        ]);
    }

    public function index()
    {
        return view('tts.index');
    }
}
