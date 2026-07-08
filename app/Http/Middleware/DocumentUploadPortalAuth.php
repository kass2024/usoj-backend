<?php

namespace App\Http\Middleware;

use App\Models\DocumentUploadLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentUploadPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $linkId = session('document_upload_link_id');

        if (!$linkId) {
            return redirect()
                ->route('document-portal.login', $slug)
                ->with('error', 'Please log in to continue.');
        }

        $link = DocumentUploadLink::find($linkId);

        if (!$link || $link->slug !== $slug || !$link->isUsable()) {
            session()->forget(['document_upload_link_id', 'document_upload_link_slug']);

            return redirect()
                ->route('document-portal.login', $slug)
                ->with('error', 'This upload link is invalid or inactive.');
        }

        $request->attributes->set('document_upload_link', $link);

        return $next($request);
    }
}
