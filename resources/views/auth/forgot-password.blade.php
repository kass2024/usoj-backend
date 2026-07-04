<x-guest-layout>

    <h2>Reset password</h2>
    <p class="lead">Enter your email and we'll send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-ok"><i class="ri-checkbox-circle-line"></i> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="fld">
            <label for="email">Email</label>
            <div class="ig">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autofocus placeholder="you@example.com">
                <i class="ri-mail-line"></i>
            </div>
            @error('email')<div class="fld-err">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-login">
            Send reset link <i class="ri-mail-send-line"></i>
        </button>

        <p style="text-align:center;margin-top:1rem">
            <a href="{{ route('login') }}" class="link"><i class="ri-arrow-left-line"></i> Back to sign in</a>
        </p>
    </form>
</x-guest-layout>
