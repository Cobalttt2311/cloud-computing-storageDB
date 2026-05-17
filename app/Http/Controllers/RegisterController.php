<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Exception;

class RegisterController extends Controller
{
    public function create()
    {
        return view('register.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'foto'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $file = $request->file('foto');
        $containerName = 'pelamarfiles'; 

        $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        $extension = $file->getClientOriginalExtension();
        $blobName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        try {
            $fileStream = fopen($file->getRealPath(), 'r');
            $blobClient->createBlockBlob($containerName, $blobName, $fileStream);

            $accountName = 'cloudcomputing209';
            $publicUrl = "https://{$accountName}.blob.core.windows.net/{$containerName}/{$blobName}";

            DB::table('pelamar')->insert([
                'nama'    => $validated['nama'],
                'email'   => $validated['email'],
                'ktp_url' => $publicUrl,
            ]);

            return redirect()->route('register.index')->with('success', 'Pendaftaran berhasil!');
        } catch (ServiceException $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah file: ' . $e->getMessage());
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $pelamar = DB::table('pelamar')->orderBy('id', 'desc')->get();
        return view('register.index', compact('pelamar'));
    }

    public function edit($id)
    {
        $pelamar = DB::table('pelamar')->where('id', $id)->first();
        if (!$pelamar) {
            return redirect()->route('register.index')->with('error', 'Data tidak ditemukan.');
        }
        return view('register.edit', compact('pelamar'));
    }

    public function update(Request $request, $id)
    {
        $pelamar = DB::table('pelamar')->where('id', $id)->first();
        if (!$pelamar) {
            return redirect()->route('register.index')->with('error', 'Data tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'foto'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $containerName = 'pelamarfiles';
        $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');
        $blobClient = BlobRestProxy::createBlobService($connectionString);
        $accountName = 'cloudcomputing209';

        $ktp_url = $pelamar->ktp_url;

        try {
            if ($request->hasFile('foto')) {
                $oldUrl = $pelamar->ktp_url;
                if ($oldUrl) {
                    $path = parse_url($oldUrl, PHP_URL_PATH); 
                    $oldBlobName = ltrim($path, '/' . $containerName . '/');
                    if ($oldBlobName) {
                        try {
                            $blobClient->deleteBlob($containerName, $oldBlobName);
                        } catch (\Exception $e) {
                        }
                    }
                }

                $file = $request->file('foto');
                $blobName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $fileStream = fopen($file->getRealPath(), 'r');
                $blobClient->createBlockBlob($containerName, $blobName, $fileStream);
                $ktp_url = "https://{$accountName}.blob.core.windows.net/{$containerName}/{$blobName}";
            }

            DB::table('pelamar')->where('id', $id)->update([
                'nama'    => $validated['nama'],
                'email'   => $validated['email'],
                'ktp_url' => $ktp_url,
            ]);

            return redirect()->route('register.index')->with('success', 'Data berhasil diperbarui.');
        } catch (ServiceException $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $pelamar = DB::table('pelamar')->where('id', $id)->first();
        if (!$pelamar) {
            return redirect()->route('register.index')->with('error', 'Data tidak ditemukan.');
        }

        $containerName = 'pelamarfiles';
        $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');
        $blobClient = BlobRestProxy::createBlobService($connectionString);
        $oldUrl = $pelamar->ktp_url;
        if ($oldUrl) {
            $path = parse_url($oldUrl, PHP_URL_PATH);
            $oldBlobName = ltrim($path, '/' . $containerName . '/');
            if ($oldBlobName) {
                try {
                    $blobClient->deleteBlob($containerName, $oldBlobName);
                } catch (\Exception $e) {
                }
            }
        }

        DB::table('pelamar')->where('id', $id)->delete();

        return redirect()->route('register.index')->with('success', 'Data berhasil dihapus.');
    }
}