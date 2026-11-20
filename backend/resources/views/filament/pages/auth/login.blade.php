<div class="auth-wrapper">
    <!-- Left Side: Hero Banner with School Building & Blue Gradient (Edge-to-Edge Fullscreen) -->
    <div class="auth-hero">
        <!-- Top Logo & School Name -->
        <div class="auth-hero-header">
            <img src="{{ asset('images/logo-tutwuri.png') }}" alt="SMKN 1 Gunung Putri" class="auth-hero-logo" />
            <div class="auth-school-title">
                <span>SMKN 1</span>
                <span>GUNUNG PUTRI</span>
            </div>
        </div>

        <!-- Middle Headline -->
        <div class="auth-hero-body">
            <h1 class="auth-headline">
                <span class="auth-headline-main">Sistem Absensi</span>
                <span class="auth-headline-accent">Digital</span>
            </h1>
            <div class="auth-headline-underline"></div>
            <p class="auth-hero-desc">
                Solusi absensi modern untuk lingkungan sekolah yang lebih disiplin dan terorganisir.
            </p>
        </div>

        <!-- Bottom Slogan -->
        <div class="auth-hero-footer">
            <span class="auth-footer-bar"></span>
            <span class="auth-footer-text">Disiplin Hari Ini, Prestasi Esok Nanti</span>
        </div>
    </div>

    <!-- Right Side: Login Form (Edge-to-Edge Fullscreen) -->
    <div class="auth-form-side">
        <!-- Decorative curve in bottom right -->
        <div class="auth-bg-wave"></div>

        <div class="auth-form-content">
            <!-- Center School Emblem -->
            <div class="auth-emblem-wrapper">
                <img src="{{ asset('images/logo-tutwuri.png') }}" alt="SMKN 1 Gunung Putri" class="auth-emblem" />
            </div>

            <h2 class="auth-form-title">Selamat Datang</h2>
            <p class="auth-form-subtitle">Silakan login untuk melanjutkan</p>

            <form wire:submit="authenticate" class="auth-form">
                @if ($errors->any())
                    <div class="auth-error-alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Input Username atau NIS -->
                <div class="auth-input-group">
                    <x-heroicon-m-user class="auth-input-icon" />
                    <input
                        wire:model="data.email"
                        type="text"
                        placeholder="Username atau NIS"
                        required
                        autocomplete="username"
                        autofocus
                        class="auth-input"
                    />
                </div>

                <!-- Input Password with Eye Toggle -->
                <div x-data="{ show: false }" class="auth-input-group">
                    <x-heroicon-m-lock-closed class="auth-input-icon" />
                    <input
                        wire:model="data.password"
                        :type="show ? 'text' : 'password'"
                        placeholder="Password"
                        required
                        autocomplete="current-password"
                        class="auth-input"
                    />
                    <button type="button" @click="show = !show" class="auth-eye-btn" tabindex="-1">
                        <x-heroicon-m-eye x-show="!show" class="auth-eye-icon" />
                        <x-heroicon-m-eye-slash x-show="show" class="auth-eye-icon" />
                    </button>
                </div>

                <!-- Tombol Masuk -->
                <button type="submit" class="auth-submit-btn">
                    <span wire:loading.remove wire:target="authenticate">Masuk</span>
                    <span wire:loading wire:target="authenticate">Memproses...</span>
                    <x-heroicon-m-arrow-right class="auth-arrow-icon" />
                </button>

                <!-- Divider Footer -->
                <div class="auth-divider">
                    <span class="auth-divider-line"></span>
                    <span class="auth-divider-text">Absensi lebih mudah, lebih cepat</span>
                    <span class="auth-divider-line"></span>
                </div>
            </form>
        </div>
    </div>
</div>
