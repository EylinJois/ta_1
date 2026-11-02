<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Overtrue\Pinyin\Pinyin;
use Afaya\EdgeTTS\Service\EdgeTTS;

class TTSController extends Controller
{
    protected function logTtsError(string $context, \Throwable $e): void
    {
        Log::error($context . ': ' . $e->getMessage(), [
            'exception' => $e,
        ]);
    }

    public function generate(Request $request)
    {
        try {
            $request->validate([
                'text' => 'required|string|max:5000',
            ]);

            $text = trim($request->text);

            if ($text === '') {
                return response()->json([
                    'message' => 'Text cannot be empty.',
                ], 422);
            }

            $pinyin = implode(' ', (new Pinyin())->sentence($text)->toArray());
            $pinyin = preg_replace('/\s+([，。！？；：])/u', '$1', $pinyin);

            $hanziCount = preg_match_all('/\p{Han}/u', $text);

            $tts = new EdgeTTS();
            $tts->synthesize($text, 'zh-CN-XiaoxiaoNeural');
            $audio = $tts->toRaw();

            if (empty($audio)) {
                return response()->json([
                    'message' => 'Unable to generate audio right now.',
                ], 500);
            }

            $payload = [
                'hanzi' => $text,
                'pinyin' => $pinyin,
                'hanzi_count' => $hanziCount,
            ];

            return response()->json($payload + [
                'audio' => base64_encode($audio),
                'type' => 'stream',
            ]);
        } catch (Throwable $e) {
            Log::error('TTS generation failed', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Unable to generate audio. Please try again later.',
            ], 500);
        }
    }

    public function index()
    {
        return view('tts.index');
    }
}
