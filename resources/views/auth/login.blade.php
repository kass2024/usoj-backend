<x-guest-layout>

    <h2>Welcome back</h2>
    <p class="lead">Sign in with your email and password.<br>Students and staff use the same portal.</p>

    @if (session('status'))
        <div class="alert alert-ok"><i class="ri-checkbox-circle-line"></i> {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-bad"><i class="ri-error-warning-line"></i> {{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="fld">
            <label for="email">Email</label>
            <div class="ig">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username" placeholder="you@example.com">
                <i class="ri-mail-line"></i>
            </div>
            @error('email')<div class="fld-err">{{ $message }}</div>@enderror
        </div>

        <div class="fld">
            <label for="password">Password</label>
            <div class="ig">
                <input id="password" type="password" name="password" required
                       class="pw" autocomplete="current-password" placeholder="Enter password">
                <i class="ri-lock-2-line"></i>
                <button type="button" class="eye" id="eye-btn" aria-label="Show password">
                    <i class="ri-eye-line"></i>
                </button>
            </div>
            @error('password')<div class="fld-err">{{ $message }}</div>@enderror
        </div>

        <div class="fld-row">
            <label class="chk">
                <input type="checkbox" name="remember" id="remember">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-login">
            Sign in <i class="ri-arrow-right-line"></i>
        </button>

        <p class="secure">
            <i class="ri-shield-keyhole-line"></i> Secured · Authorized users only
        </p>
    </form>

    <script>
        document.getElementById('eye-btn')?.addEventListener('click', function () {
            const p = document.getElementById('password');
            const on = p.type === 'password';
            p.type = on ? 'text' : 'password';
            this.innerHTML = on ? '<i class="ri-eye-off-line"></i>' : '<i class="ri-eye-line"></i>';
        });
    </script>
</x-guest-layout>
