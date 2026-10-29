<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupUploadRequest;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request): View
    {
        return view('backup', ['preview' => $request->session()->get('backup_preview')]);
    }

    public function export(Request $request, BackupService $backup): StreamedResponse
    {
        $data = $backup->export($request->user());

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'handoff-hub-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    private function cacheKey(Request $request, string $nonce): string
    {
        return 'backup:'.$request->user()->id.':'.$nonce;
    }

    public function preview(BackupUploadRequest $request, BackupService $backup): RedirectResponse
    {
        $data = $backup->validate(file_get_contents($request->file('backup')->getRealPath()));
        if ($old = $request->session()->get('backup_nonce')) {
            Cache::forget($this->cacheKey($request, $old));
        }
        $nonce = (string) Str::uuid();
        Cache::put($this->cacheKey($request, $nonce), $data, now()->addMinutes(15));
        $request->session()->put(['backup_nonce' => $nonce, 'backup_preview' => $backup->preview($request->user(), $data)]);

        return redirect()->route('backup.index');
    }

    public function import(Request $request, BackupService $backup): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']]);
        $nonce = $request->session()->get('backup_nonce');
        $key = $nonce ? $this->cacheKey($request, $nonce) : '';
        $data = $key ? Cache::get($key) : null;
        if (! $data) {
            throw ValidationException::withMessages(['backup' => 'Pratinjau kedaluwarsa. Unggah kembali backup.']);
        }
        $result = $backup->import($request->user(), $data);
        Cache::forget($key);
        $request->session()->forget(['backup_nonce', 'backup_preview']);

        return redirect()->route('backup.index')->with('success', "Impor selesai: {$result['projects']} proyek baru, {$result['checkpoints']} checkpoint baru, {$result['skipped']} checkpoint duplikat dilewati.");
    }
}
