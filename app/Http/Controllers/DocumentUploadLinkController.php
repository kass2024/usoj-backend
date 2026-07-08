<?php

namespace App\Http\Controllers;

use App\Models\DocumentUploadLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentUploadLinkController extends Controller
{
    public function index()
    {
        $links = DocumentUploadLink::with('creator')
            ->latest()
            ->get();

        return view('document-links.index', compact('links'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $username = $this->uniqueUsername();
        $password = $this->generatePassword();
        $name = 'USJ Upload ' . now()->format('d M Y H:i');

        $link = DocumentUploadLink::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'password_plain' => $password,
            'slug' => Str::random(32),
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('document-links.index')
            ->with('success', 'Private upload link created. Use Copy to share via WhatsApp or email.')
            ->with('created_link_id', $link->id);
    }

    public function toggle(DocumentUploadLink $link)
    {
        $link->update(['is_active' => !$link->is_active]);

        return back()->with('success', $link->is_active ? 'Link activated.' : 'Link deactivated.');
    }

    public function destroy(DocumentUploadLink $link)
    {
        $link->delete();

        return back()->with('success', 'Upload link deleted.');
    }

    private function uniqueUsername(): string
    {
        $next = DocumentUploadLink::count() + 1;

        do {
            $username = 'uosj.docs.' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (DocumentUploadLink::where('username', $username)->exists());

        return $username;
    }

    private function generatePassword(): string
    {
        // University-branded + short unique suffix (easy to share)
        $suffix = str_pad((string) ((DocumentUploadLink::max('id') ?? 0) + 1), 3, '0', STR_PAD_LEFT);

        return 'UOSJ@' . now()->format('Y') . '#' . $suffix;
    }
}
