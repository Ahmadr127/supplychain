@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="relative min-h-screen w-full overflow-hidden bg-white">
    <!-- Subtle wave patterns -->
    <div class="absolute inset-x-0 -top-24 h-64" style="background: radial-gradient(70% 60% at 50% 0%, #E6FAF2 0%, rgba(230,250,242,0) 70%);"></div>
    <div class="absolute inset-x-0 -bottom-24 h-64" style="background: radial-gradient(70% 60% at 50% 100%, #EAFBF7 0%, rgba(234,251,247,0) 70%);"></div>

    <div class="relative mx-auto w-full max-w-lg px-4 sm:px-6 lg:px-8 py-12">
        <!-- Glass card -->
        <div class="backdrop-blur-xl bg-white/70 border border-white/40 shadow-2xl rounded-3xl p-8 sm:p-10">
            <!-- Logo -->
            <div class="flex flex-col items-center mb-6">
                <div class="rounded-2xl border border-[#0f766e]/20 bg-white shadow-md p-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-12 object-contain" />
                </div>
                <p class="mt-3 text-xs font-semibold tracking-wide text-[#006D7A]">Library Management System</p>
            </div>

            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 text-center mb-6">Log in</h1>

            <!-- Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input id="username" name="username" type="text" required placeholder="yourname" value="{{ old('username') }}"
                           class="w-full rounded-xl border border-gray-300 px-4 py-3 bg-white/80 text-gray-900 shadow-sm outline-none transition focus:ring-4 focus:ring-[#006D7A]/20 focus:border-[#006D7A] hover:border-gray-400" />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <a href="#" class="text-sm text-[#006D7A] hover:text-[#005F66]">Forgot Password?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" required placeholder="••••••••"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 pr-11 bg-white/80 text-gray-900 shadow-sm outline-none transition focus:ring-4 focus:ring-[#006D7A]/20 focus:border-[#006D7A] hover:border-gray-400" />
                        <button type="button" id="togglePassword" aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-[#006D7A] focus:outline-none">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-3 rounded-lg text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#006D7A] to-[#005F66] text-white font-semibold py-3 shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-[#006D7A]/30">
                    Log in
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    (function(){
        const toggleBtn = document.getElementById('togglePassword');
        const pwd = document.getElementById('password');
        if (toggleBtn && pwd) {
            toggleBtn.addEventListener('click', function(){
                const isHidden = pwd.type === 'password';
                pwd.type = isHidden ? 'text' : 'password';
                this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                this.innerHTML = isHidden ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
            });
        }
    })();
</script>
@endsection
