<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload External Documents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f5; font-family: Arial, Helvetica, sans-serif; }
        .brand { color: #007a33; font-weight: 700; }

        .upload-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 40, 20, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .upload-overlay.active { display: flex; }
        .upload-panel {
            background: #fff;
            border-radius: 14px;
            padding: 28px 32px;
            width: min(360px, 92vw);
            text-align: center;
            box-shadow: 0 16px 40px rgba(0,0,0,.2);
        }
        .spinner-ring {
            width: 52px;
            height: 52px;
            border: 4px solid #d9eee1;
            border-top-color: #007a33;
            border-radius: 50%;
            margin: 0 auto 14px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .progress-track {
            height: 8px;
            background: #e8f2eb;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 14px;
        }
        .progress-bar-smart {
            height: 100%;
            width: 8%;
            background: linear-gradient(90deg, #007a33, #24b35b);
            border-radius: 99px;
            transition: width .25s ease;
        }

        .success-toast {
            position: fixed;
            top: 18px;
            left: 50%;
            transform: translateX(-50%) translateY(-120%);
            background: #0f7a38;
            color: #fff;
            padding: 14px 18px;
            border-radius: 12px;
            box-shadow: 0 10px 28px rgba(0,0,0,.18);
            z-index: 2100;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: min(440px, 92vw);
            opacity: 0;
            transition: transform .35s ease, opacity .35s ease;
        }
        .success-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        .success-toast .check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<div class="container py-4" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="brand mb-0">External Document Upload</h4>
            <small class="text-muted">{{ $link->name }}</small>
        </div>
        <form method="post" action="{{ route('document-portal.logout', $link->slug) }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Logout</button>
        </form>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="mb-3">1. Look up student</h6>
            <form method="post" action="{{ route('document-portal.lookup', $link->slug) }}" class="row g-2" id="lookup-form">
                @csrf
                <div class="col-md-8">
                    <input type="text"
                           name="reg_number"
                           class="form-control"
                           value="{{ old('reg_number') }}"
                           placeholder="Enter registration number e.g. 21MEE001"
                           required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-success w-100" type="submit">Find Student</button>
                </div>
            </form>
        </div>
    </div>

    @isset($student)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-2">Student found</h6>
                <p class="mb-1"><strong>{{ $student->fname }} {{ $student->lname }}</strong></p>
                <p class="mb-1">Reg No: {{ $student->reg_number }}</p>
                @if ($student->department)
                    <p class="mb-0 text-muted">{{ $student->department->name }}</p>
                @endif

                @if ($externalTranscript || $externalDegree)
                    <hr>
                    <p class="mb-1"><strong>Already uploaded:</strong></p>
                    <ul class="mb-0">
                        @if ($externalTranscript)
                            <li>External Transcript ({{ $externalTranscript->original_name }})</li>
                        @endif
                        @if ($externalDegree)
                            <li>External Degree ({{ $externalDegree->original_name }})</li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="mb-2">2. Upload document</h6>
                <p class="small text-muted">
                    You can upload a <strong>transcript</strong> and a <strong>degree</strong> for the same student.
                    Re-uploading the same type replaces the previous file.
                </p>

                <form method="post"
                      action="{{ route('document-portal.upload', $link->slug) }}"
                      enctype="multipart/form-data"
                      class="row g-3"
                      id="upload-form">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Document type</label>
                        <select name="type" class="form-select" required>
                            <option value="transcript">Transcript</option>
                            <option value="degree" @selected(old('type') === 'degree')>Degree</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">File (PDF / JPG / PNG, max 10MB)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit" id="upload-btn">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endisset
</div>

<div class="upload-overlay" id="upload-overlay" aria-hidden="true">
    <div class="upload-panel">
        <div class="spinner-ring"></div>
        <h6 class="mb-1" id="upload-status-title">Uploading document…</h6>
        <p class="text-muted small mb-0" id="upload-status-text">Please wait while your file is securely uploaded.</p>
        <div class="progress-track">
            <div class="progress-bar-smart" id="upload-progress"></div>
        </div>
    </div>
</div>

@if (session('upload_success'))
    <div class="success-toast" id="success-toast">
        <div class="check">✓</div>
        <div>
            <strong>{{ session('upload_success') }}</strong>
            <div class="small" style="opacity:.9;">Ready for registry viewing and download.</div>
        </div>
    </div>
@endif

<script>
    (function () {
        const overlay = document.getElementById('upload-overlay');
        const progress = document.getElementById('upload-progress');
        const title = document.getElementById('upload-status-title');
        const text = document.getElementById('upload-status-text');
        const uploadForm = document.getElementById('upload-form');
        const uploadBtn = document.getElementById('upload-btn');
        let progressTimer = null;

        function startOverlay(label) {
            if (!overlay) return;
            overlay.classList.add('active');
            if (title) title.textContent = label || 'Working…';
            if (text) text.textContent = 'Please wait…';
            if (progress) progress.style.width = '8%';

            let value = 8;
            clearInterval(progressTimer);
            progressTimer = setInterval(function () {
                value = Math.min(value + Math.random() * 12, 90);
                if (progress) progress.style.width = value + '%';
            }, 280);
        }

        if (uploadForm) {
            uploadForm.addEventListener('submit', function () {
                if (uploadBtn) {
                    uploadBtn.disabled = true;
                    uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading';
                }
                startOverlay('Uploading document…');
                if (text) text.textContent = 'Sending file to University of Saint Joseph Mbarara servers.';
            });
        }

        const lookupForm = document.getElementById('lookup-form');
        if (lookupForm) {
            lookupForm.addEventListener('submit', function () {
                startOverlay('Finding student…');
                if (text) text.textContent = 'Checking registration number.';
            });
        }

        const toast = document.getElementById('success-toast');
        if (toast) {
            requestAnimationFrame(function () {
                toast.classList.add('show');
            });
            setTimeout(function () {
                toast.classList.remove('show');
            }, 4500);
        }
    })();
</script>
</body>
</html>
