<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your profile information, email address, and profile photo.") }}
        </p>
    </header>

    @if (session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    <form method="post" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="profile_img" :value="__('Profile Photo')" />

            @php
                $photoUrl = \App\Support\StudentPhoto::url($student);
            @endphp

            <div class="mb-2">
                <img src="{{ $photoUrl }}"
                     alt="Profile Photo"
                     class="rounded border"
                     style="width:80px;height:80px;object-fit:cover;">
            </div>

            <x-text-input id="profile_img" name="profile_img" type="file"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer focus:outline-none"
                accept="image/jpeg,image/png,image/jpg" />

            <x-input-error class="mt-2" :messages="$errors->get('profile_img')" />
        </div>

        <div class="mb-3">
            <x-input-label for="fname" :value="__('First Name')" />
            <x-text-input id="fname" name="fname" type="text" class="block w-full" :value="old('fname', $student->fname)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('fname')" />
        </div>

        <div class="mb-3">
            <x-input-label for="lname" :value="__('Last Name')" />
            <x-text-input id="lname" name="lname" type="text" class="block w-full" :value="old('lname', $student->lname)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('lname')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $student->email)" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="text" class="block w-full" :value="old('phone', $student->phone)" required autocomplete="tel" />
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
