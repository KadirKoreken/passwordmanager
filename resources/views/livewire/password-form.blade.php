<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold" style="color: #FFFFFF;">
            {{ $passwordId ? 'Şifre Düzenle' : 'Yeni Şifre Ekle' }}
        </h3>
        <button wire:click="$parent.closeForm"
                class="p-2 rounded-lg transition-all duration-200 hover:scale-110"
                style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                onmouseover="this.style.backgroundColor='#000000'; this.style.color='#FFFFFF'"
                onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Başlık -->
        <div>
            <label for="title" class="block text-sm font-semibold mb-2" style="color: #E5E5E5;">
                Başlık *
            </label>
            <input type="text"
                   id="title"
                   wire:model="title"
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:border-transparent transition-all duration-200"
                   style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5; color: #FFFFFF; backdrop-filter: blur(10px);"
                   onfocus="this.style.borderColor='#FCA311'; this.style.boxShadow='0 0 0 2px rgba(252, 163, 17, 0.2)'"
                   onblur="this.style.borderColor='#E5E5E5'; this.style.boxShadow='none'"
                   placeholder="Örn: Facebook">
            @error('title')
                <p class="mt-1 text-sm" style="color: #FCA311;">{{ $message }}</p>
            @enderror
        </div>

        <!-- URL -->
        <div>
            <label for="url" class="block text-sm font-semibold mb-2" style="color: #E5E5E5;">
                URL (İsteğe bağlı)
            </label>
            <input type="url"
                   id="url"
                   wire:model="url"
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:border-transparent transition-all duration-200"
                   style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5; color: #FFFFFF; backdrop-filter: blur(10px);"
                   onfocus="this.style.borderColor='#FCA311'; this.style.boxShadow='0 0 0 2px rgba(252, 163, 17, 0.2)'"
                   onblur="this.style.borderColor='#E5E5E5'; this.style.boxShadow='none'"
                   placeholder="https://facebook.com">
            @error('url')
                <p class="mt-1 text-sm" style="color: #FCA311;">{{ $message }}</p>
            @enderror
        </div>

        <!-- Kullanıcı Adı -->
        <div>
            <label for="username" class="block text-sm font-semibold mb-2" style="color: #E5E5E5;">
                Kullanıcı Adı / E-posta *
            </label>
            <input type="text"
                   id="username"
                   wire:model="username"
                   class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:border-transparent transition-all duration-200"
                   style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5; color: #FFFFFF; backdrop-filter: blur(10px);"
                   onfocus="this.style.borderColor='#FCA311'; this.style.boxShadow='0 0 0 2px rgba(252, 163, 17, 0.2)'"
                   onblur="this.style.borderColor='#E5E5E5'; this.style.boxShadow='none'"
                   placeholder="demo@facebook.com">
            @error('username')
                <p class="mt-1 text-sm" style="color: #FCA311;">{{ $message }}</p>
            @enderror
        </div>

        <!-- Şifre -->
        <div>
            <label for="password" class="block text-sm font-semibold mb-2" style="color: #E5E5E5;">
                Şifre *
            </label>
            <div class="relative">
                <input type="{{ $showPassword ? 'text' : 'password' }}"
                       id="password"
                       wire:model="password"
                       class="w-full px-4 py-3 pr-20 rounded-xl border focus:outline-none focus:ring-2 focus:border-transparent transition-all duration-200"
                       style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5; color: #FFFFFF; backdrop-filter: blur(10px);"
                       onfocus="this.style.borderColor='#FCA311'; this.style.boxShadow='0 0 0 2px rgba(252, 163, 17, 0.2)'"
                       onblur="this.style.borderColor='#E5E5E5'; this.style.boxShadow='none'"
                       placeholder="Güçlü bir şifre girin">
                <div class="absolute inset-y-0 right-0 flex items-center space-x-1 pr-3">
                    <button type="button"
                            wire:click="togglePasswordVisibility"
                            class="p-1 rounded-lg transition-all duration-200"
                            style="color: #E5E5E5;"
                            onmouseover="this.style.color='#FCA311'"
                            onmouseout="this.style.color='#E5E5E5'"
                            title="Şifreyi {{ $showPassword ? 'gizle' : 'göster' }}">
                        @if($showPassword)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.465 8.465M14.12 14.12l1.415 1.415M14.12 14.12L18.75 18.75m-4.63-4.63L12 12"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        @endif
                    </button>
                    <button type="button"
                            wire:click="generatePassword"
                            class="p-1 rounded-lg transition-all duration-200"
                            style="color: #E5E5E5;"
                            onmouseover="this.style.color='#FCA311'"
                            onmouseout="this.style.color='#E5E5E5'"
                            title="Güçlü şifre oluştur">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
            @error('password')
                <p class="mt-1 text-sm" style="color: #FCA311;">{{ $message }}</p>
            @enderror
        </div>

        <!-- Butonlar -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <button type="submit"
                    class="flex-1 text-white font-semibold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center"
                    style="background: linear-gradient(135deg, #FCA311 0%, #14213D 100%);">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $passwordId ? 'Güncelle' : 'Kaydet' }}
            </button>
            <button type="button"
                    wire:click="$parent.closeForm"
                    class="flex-1 font-semibold py-4 px-6 rounded-xl border transition-all duration-200 hover:scale-105"
                    style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5; border-color: #E5E5E5;"
                    onmouseover="this.style.backgroundColor='#14213D'; this.style.color='#FFFFFF'; this.style.borderColor='#14213D'"
                    onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'; this.style.borderColor='#E5E5E5'">
                İptal
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('close-form', () => {
            @this.dispatch('close-form');
        });
    });
</script>
