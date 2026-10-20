<?php

namespace App\Console\Commands;

use App\Http\Controllers\TTSController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTts extends Command
{
    protected $signature = 'tts:cleanup';
    protected $description = 'Delete expired temporary TTS audio files';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subMinutes(TTSController::TTL_MINUTES)->timestamp;

        $expired = collect($disk->files(TTSController::TEMP_DIR))
            ->filter(fn ($file) => $disk->lastModified($file) < $cutoff);

        $disk->delete($expired->all());

        $this->info("Deleted {$expired->count()} expired file(s).");

        return self::SUCCESS;
    }
}