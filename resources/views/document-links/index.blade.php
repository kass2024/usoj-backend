@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">USJ Private Upload Links</h5>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('document-links.store') }}" method="post" class="row g-3 mb-4 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Expires (optional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Create Private Link
                            </button>
                        </div>
                        <div class="col-md-5">
                            <p class="text-muted small mb-0">
                                Username, password, and link are generated automatically.
                                Use <strong>Copy</strong> to paste a ready WhatsApp / email message.
                            </p>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Private Link</th>
                                    <th>Status</th>
                                    <th>Expires</th>
                                    <th style="min-width:260px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($links as $link)
                                    @php
                                        $portalUrl = route('document-portal.login', $link->slug);
                                        $shareText = "University of Saint Joseph Mbarara (USJ)\n"
                                            . "Private Document Upload Access\n\n"
                                            . "Link: {$portalUrl}\n"
                                            . "Username: {$link->username}\n"
                                            . "Password: " . ($link->password_plain ?: '[ask registry to recreate link]') . "\n\n"
                                            . "Please open the link, log in, enter the student registration number,\n"
                                            . "then upload an external transcript and/or degree.";
                                    @endphp
                                    <tr>
                                        <td>{{ $link->name }}</td>
                                        <td><code>{{ $link->username }}</code></td>
                                        <td>
                                            <a href="{{ $portalUrl }}" target="_blank">Open portal</a>
                                            <div class="small text-muted text-break">{{ $portalUrl }}</div>
                                            <textarea id="share-text-{{ $link->id }}" class="d-none" readonly>{{ $shareText }}</textarea>
                                        </td>
                                        <td>
                                            @if ($link->isUsable())
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $link->expires_at?->format('d M Y H:i') ?: 'Never' }}</td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary copy-share-btn"
                                                    data-target="share-text-{{ $link->id }}">
                                                Copy
                                            </button>
                                            <form action="{{ route('document-links.toggle', $link) }}" method="post" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    {{ $link->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('document-links.destroy', $link) }}"
                                                  method="post"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this upload link?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No private links yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.copy-share-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const targetId = this.getAttribute('data-target');
                const source = document.getElementById(targetId);
                const text = source ? source.value : '';
                const original = this.textContent;

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        source.classList.remove('d-none');
                        source.select();
                        document.execCommand('copy');
                        source.classList.add('d-none');
                    }

                    this.textContent = 'Copied!';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-outline-primary');
                    setTimeout(() => {
                        this.textContent = original;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }, 1500);
                } catch (e) {
                    alert('Could not copy. Please copy manually.');
                }
            });
        });
    </script>
@endsection
