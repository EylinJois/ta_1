<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Overtrue\Pinyin\Pinyin;
use Afaya\EdgeTTS\Service\EdgeTTS;

class TTSController extends Controller
{
    const STREAM_LIMIT = 200;

    public function generate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = trim($request->text);

        // Generate pinyin
        $pinyinCollection = (new Pinyin())->sentence($text);

        // Join with spaces instead of commas
        $pinyin = implode(' ', $pinyinCollection->toArray());

        // Remove spaces before Chinese punctuation
        $pinyin = preg_replace('/\s+([，。！？；：])/u', '$1', $pinyin);

        // Count Hanzi only
        preg_match_all('/\p{Han}/u', $text, $matches);
        $hanziCount = count($matches[0]);

        $tts = new EdgeTTS();

        $tts->synthesize($text, 'zh-CN-XiaoxiaoNeural');

        /*
        |--------------------------------------------------------------------------
        | SHORT TEXT
        |--------------------------------------------------------------------------
        */

        if ($hanziCount <= self::STREAM_LIMIT) {

            return response()->json([
                'hanzi' => $text,
                'pinyin' => $pinyin,
                'audio' => base64_encode($tts->toRaw()),
                'type' => 'stream',
                'hanzi_count' => $hanziCount,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | LONG TEXT
        |--------------------------------------------------------------------------
        */

        $directory = public_path('tts');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = sha1($text) . '.mp3';

        if (! File::exists($directory . '/' . $filename)) {
            $tts->toFile($directory . '/' . $filename);
        }

        return response()->json([
            'hanzi' => $text,
            'pinyin' => $pinyin,
            'audio' => asset("tts/$filename"),
            'type' => 'file',
            'hanzi_count' => $hanziCount,
        ]);
    }

    public function index()
    {
        return view('tts.index');
    }
}
