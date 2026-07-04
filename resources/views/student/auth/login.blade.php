@extends('layouts.student.guest')

@section('body')
<style>
  :root{
    --bg:#f5f7fb; --fg:#0f172a; --muted:#6b7280;
    --card:#ffffff; --card-bd:#e5e7ef;
    --brand:#007a33; --brand2:#005a26;
    --shadow:0 12px 32px rgba(15,23,42,.08);
    --radius:16px;

    --header-bg:#007a33; --header-fg:#ffffff; --header-h:64px;
    --footer-bg:#0f172a; --footer-fg:#e5e7eb; --footer-h:44px;
  }
  [data-theme="dark"]{
    --bg:#0b1220; --fg:#e5e7eb; --muted:#94a3b8;
    --card:#0f172a; --card-bd:#1f2a44;
    --brand:#4ade80; --brand2:#22c55e;
    --shadow:0 14px 36px rgba(0,0,0,.55);
    --header-bg:#1e293b; --header-fg:#e5e7eb;
    --footer-bg:#111827; --footer-fg:#94a3b8;
  }

  html, body{height:100%; margin:0; background:var(--bg); color:var(--fg); overflow:hidden}

  /* Header */
  .auth-header{
    position:fixed; inset:0 0 auto 0; height:var(--header-h);
    background:var(--header-bg); color:var(--header-fg);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 16px; z-index:1000; box-shadow:0 6px 20px rgba(0,0,0,.15);
  }
  .auth-header .brand{display:flex; align-items:center; gap:.6rem; font-weight:900}
  .auth-header .brand img{height:32px}
  .auth-header .links a{color:var(--header-fg); text-decoration:none; font-weight:700}
  .auth-header .links a:hover{opacity:.85}

  /* Footer */
  .auth-footer{
    position:fixed; inset:auto 0 0 0; height:var(--footer-h);
    background:var(--footer-bg); color:var(--footer-fg);
    display:flex; align-items:center; justify-content:center;
    font-size:.9rem; font-weight:600; padding:0 12px;
    box-shadow:0 -6px 20px rgba(0,0,0,.10); z-index:900;
  }

  /* Middle: flex center */
  .band{
    position:fixed; left:0; right:0;
    top:var(--header-h); bottom:var(--footer-h);
    display:flex; align-items:center; justify-content:center;
    padding:12px;
  }

  /* Login Card */
  .auth-card{
    width:100%; max-width:460px;
    background:var(--card); border:1px solid var(--card-bd);
    border-radius:var(--radius); box-shadow:var(--shadow);
  }
  .auth-body{padding:28px 24px}
  .form-title{font-weight:900; margin:0}
  .form-sub{color:var(--muted); font-weight:600; margin-bottom:12px}
  .form-label{font-weight:800; margin-bottom:6px}

  .btn-primary-strong{
    display:inline-flex; align-items:center; justify-content:center; gap:.45rem;
    width:100%; border:1px solid transparent; border-radius:12px;
    padding:.7rem 1rem; font-weight:900; color:#fff;
    background:linear-gradient(90deg,var(--brand),var(--brand2));
    box-shadow:0 10px 22px rgba(0,122,51,.25);
    transition:filter .12s ease;
  }
  .btn-primary-strong:hover{filter:brightness(1.05)}

  .aux-row{display:flex; align-items:center; justify-content:space-between; gap:10px; margin:8px 0 4px}
  .aux-row a{font-weight:700; text-decoration:none}
  .aux-row a:hover{opacity:.85}
</style>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

{{-- Header --}}
<div class="auth-header">
  <div class="brand">
    <img src="/images/usjm.png" alt="USJ Logo">
    <span>University of Saint Joseph Mbarara</span>
  </div>
  <div class="links">
    <a href="{{ route('dashboard') }}" title="Home"><i class="ri-home-4-line"></i></a>
  </div>
</div>

{{-- Middle (Centered Login Form) --}}
<div class="band">
  <div class="auth-card">
    <div class="auth-body">
      <h5 class="form-title">Sign In</h5>
      <div class="form-sub">Use your student email and password.</div>

      <x-auth-session-status class="mb-3" :status="session('status')" />
      @if (session('error'))
        <div class="alert alert-danger py-2 px-3" role="alert">
          <strong>Oops!</strong> {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('student.login') }}" class="mt-1">
        @csrf
        <div class="mb-3">
          <x-input-label for="email" :value="__('Email')" class="form-label" />
          <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus
                        autocomplete="username" placeholder="you@example.com" />
          <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="mb-2">
          <x-input-label for="password" :value="__('Password')" class="form-label" />
          <div class="position-relative auth-pass-inputgroup mb-1">
            <x-text-input id="password" class="pe-5 password-input" type="password" name="password"
                          required autocomplete="current-password" placeholder="••••••••" />
            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none password-addon"
                    type="button" id="password-addon" aria-label="Show password">
              <i class="ri-eye-fill align-middle"></i>
            </button>
          </div>
          <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="aux-row">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" name="remember" id="auth-remember-check">
            <label class="form-check-label" for="auth-remember-check">Remember me</label>
          </div>
          @if (Route::has('student.password.request'))
            <a href="{{ route('student.password.request') }}" class="text-primary">
              <i class="ri-lock-unlock-line"></i> Forgot password?
            </a>
          @endif
        </div>

        <div class="mt-3">
          <button type="submit" class="btn-primary-strong">
            <i class="ri-login-circle-line"></i> Log in
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Footer --}}
<div class="auth-footer">
  © {{ now()->year }} University of Saint Joseph Mbarara · Foster Excellence and Integrity
</div>

<script>
  // Password show/hide
  (function(){
    const btn = document.getElementById('password-addon');
    const input = document.getElementById('password');
    btn?.addEventListener('click', ()=>{
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      btn.innerHTML = type === 'password'
        ? '<i class="ri-eye-fill align-middle"></i>'
        : '<i class="ri-eye-off-fill align-middle"></i>';
    });
  })();
</script>
@endsection
