<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information, email address, and profile photo.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- Add enctype for file upload --}}
    <form method="post" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="profile_img" :value="__('Profile Photo')" />

            {{-- Show current photo if available --}}
            @if ($student->profile_photo)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $student->profile_photo) }}" 
                         alt="Profile Photo" 
                         class="w-20 h-20 rounded-full object-cover">
                </div>
            @endif

            <x-text-input id="profile_img" name="profile_img" type="file" 
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer focus:outline-none" 
                accept="image/*" />

            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

        <div class="mb-3">
            <x-input-label for="fname" :value="__('First Name')" />
            <x-text-input id="fname" name="fname" type="text" class="block w-full" :value="old('fname', $student->fname)" required
                autofocus autocomplete="fname" />
            <x-input-error class="mt-2" :messages="$errors->get('fname')" />
        </div>

        <div class="mb-3">
            <x-input-label for="lname" :value="__('Last Name')" />
            <x-text-input id="lname" name="lname" type="text" class="block w-full" :value="old('lname', $student->lname)" required
                autofocus autocomplete="lname" />
            <x-input-error class="mt-2" :messages="$errors->get('lname')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $student->email)" required
                autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="text" class="block w-full" :value="old('phone', $student->phone)"
                required />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>
        
        <div class="flex items-center gap-4 mt-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition 
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
