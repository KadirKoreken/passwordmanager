<x-app-layout>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:password-manager />
        </div>
    </div>

    @push('scripts')
    <script>
                document.addEventListener('livewire:init', () => {
                        Livewire.on('copy-to-clipboard', (event) => {
                console.log('📋 Copy event received:', event);

                // Livewire 3 named parameter formatında gelen veriyi al
                const password = event.password || event[0] || event;
                console.log('📋 Password to copy:', password);

                if (!password || password === null || password === undefined) {
                    console.error('❌ No password received!');
                    showToast('Kopyalanacak şifre bulunamadı!', 'error');
                    return;
                }

                // HTTPS ve localhost kontrolü için daha güvenilir metod
                const isSecure = window.isSecureContext ||
                               location.protocol === 'https:' ||
                               location.hostname === 'localhost' ||
                               location.hostname === '127.0.0.1';

                console.log('🔒 Is secure context:', isSecure);

                if (navigator.clipboard && isSecure) {
                    console.log('📋 Using modern clipboard API');
                    navigator.clipboard.writeText(password).then(() => {
                        console.log('✅ Clipboard API success');
                        showToast('Şifre panoya kopyalandı!', 'success');
                    }).catch((err) => {
                        console.error('❌ Clipboard API error:', err);
                        fallbackCopyToClipboard(password);
                    });
                } else {
                    console.log('📋 Using fallback method');
                    fallbackCopyToClipboard(password);
                }
            });
        });

        function fallbackCopyToClipboard(text) {
            console.log('🔄 Fallback copy method called with:', text);

            if (!text) {
                console.error('❌ Empty text for fallback copy');
                showToast('Panoya kopyalama başarısız!', 'error');
                return;
            }

            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                console.log('📋 ExecCommand result:', successful);

                if (successful) {
                    console.log('✅ Fallback copy successful');
                    showToast('Şifre panoya kopyalandı!', 'success');
                } else {
                    console.error('❌ ExecCommand returned false');
                    showToast('Panoya kopyalama başarısız!', 'error');
                }
            } catch (err) {
                console.error('❌ Fallback copy failed:', err);
                showToast('Panoya kopyalama başarısız!', 'error');
            }

            document.body.removeChild(textArea);
        }

        function showToast(message, type = 'success') {
            // Toast container'ı oluştur
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = createToastContainer();
            }

            // Toast elemanını oluştur
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type === 'success' ? 'toast-success' : 'toast-error'}`;
            toast.innerHTML = `
                <div class="flex items-center p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'success'
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
                        }
                    </svg>
                    <span class="font-medium">${message}</span>
                </div>
            `;

            toastContainer.appendChild(toast);

            // Animasyonu başlat
            setTimeout(() => {
                toast.firstElementChild.classList.remove('translate-x-full');
                toast.firstElementChild.classList.add('translate-x-0');
            }, 100);

            // Toast'ı kaldır
            setTimeout(() => {
                toast.firstElementChild.classList.add('translate-x-full');
                toast.firstElementChild.classList.remove('translate-x-0');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
            return container;
        }
    </script>

    <style>
        .toast-success .flex {
            background: linear-gradient(135deg, #FCA311 0%, #14213D 100%);
            color: white;
        }

        .toast-error .flex {
            background: linear-gradient(135deg, #000000 0%, #14213D 100%);
            color: white;
        }

        .toast-notification {
            max-width: 400px;
        }

        .translate-x-0 {
            transform: translateX(0);
        }
    </style>
    @endpush
</x-app-layout>
