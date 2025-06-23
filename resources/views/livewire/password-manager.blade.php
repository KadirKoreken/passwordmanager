<div class="min-h-screen">
    @if (session()->has('message'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 pt-6">
            <div class="text-white px-6 py-4 rounded-xl shadow-lg animate-pulse" style="background: linear-gradient(135deg, #FCA311 0%, #E5E5E5 100%);">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span style="color: #000000; font-weight: 600;">{{ session('message') }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="backdrop-blur-lg border shadow-xl rounded-2xl mb-8" style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5;">
            <div class="p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold" style="color: #FFFFFF;">
                            Şifre Yöneticisi
                        </h1>
                        <p class="mt-2" style="color: #E5E5E5;">Şifrelerinizi güvenle yönetin</p>
                    </div>
                    <button wire:click="openCreateForm"
                            class="mt-4 sm:mt-0 text-white font-semibold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center"
                            style="background: linear-gradient(135deg, #FCA311 0%, #14213D 100%);">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Yeni Şifre Ekle
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5" style="color: #E5E5E5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Şifreler arasında ara..."
                           class="w-full pl-12 pr-4 py-4 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent shadow-sm"
                           style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5; color: #FFFFFF; backdrop-filter: blur(10px);"
                           onfocus="this.style.borderColor='#FCA311'; this.style.boxShadow='0 0 0 2px rgba(252, 163, 17, 0.2)'"
                           onblur="this.style.borderColor='#E5E5E5'; this.style.boxShadow='none'">
                </div>
            </div>
        </div>

        @if($search === '' && $recentPasswords->count() > 0)
            <!-- Recent Passwords Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-6 flex items-center" style="color: #FFFFFF;">
                    <svg class="w-6 h-6 mr-3" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Son Eklenen Şifreler
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($recentPasswords->take(6) as $password)
                        <div class="group backdrop-blur-lg border rounded-xl p-6 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300" style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5;">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg group-hover:text-orange-500 transition-colors" style="color: #FFFFFF;">
                                        {{ $password->title }}
                                    </h3>
                                    @if($password->url)
                                        <a href="{{ $password->url }}" target="_blank"
                                           class="text-sm hover:underline break-all transition-colors"
                                           style="color: #E5E5E5;"
                                           onmouseover="this.style.color='#FCA311'"
                                           onmouseout="this.style.color='#E5E5E5'">
                                            {{ Str::limit($password->url, 25) }}
                                        </a>
                                    @endif
                                </div>
                                <div class="flex space-x-2">
                                    <button wire:click="copyPassword({{ $password->id }})"
                                            class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                            style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                            onmouseover="this.style.backgroundColor='#FCA311'; this.style.color='#000000'"
                                            onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                            title="Şifreyi kopyala">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="openEditForm({{ $password->id }})"
                                            class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                            style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                            onmouseover="this.style.backgroundColor='#14213D'; this.style.color='#FFFFFF'"
                                            onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                            title="Düzenle">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="deletePassword({{ $password->id }})"
                                            onclick="confirm('Bu şifreyi silmek istediğinizden emin misiniz?') || event.stopImmediatePropagation()"
                                            class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                            style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                            onmouseover="this.style.backgroundColor='#000000'; this.style.color='#FFFFFF'"
                                            onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                            title="Sil">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center text-sm" style="color: #E5E5E5;">
                                    <svg class="w-4 h-4 mr-2" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ $password->username }}
                                </div>
                                <div class="flex items-center text-xs" style="color: #E5E5E5;">
                                    <svg class="w-3 h-3 mr-1" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $password->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- All Passwords Section -->
        <div class="backdrop-blur-lg border shadow-xl rounded-2xl" style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5;">
            <div class="p-8">
                <h2 class="text-xl font-semibold mb-6 flex items-center" style="color: #FFFFFF;">
                    @if($search)
                        <svg class="w-6 h-6 mr-3" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        "{{ $search }}" için arama sonuçları
                    @else
                        <svg class="w-6 h-6 mr-3" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Tüm Şifreler
                    @endif
                </h2>

                @if($passwords->count() > 0)
                    <!-- Card View for Mobile/Tablet -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:hidden">
                        @foreach($passwords as $password)
                            <div class="border rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300" style="background: rgba(229, 229, 229, 0.1); border-color: #E5E5E5;">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="font-semibold text-lg" style="color: #FFFFFF;">{{ $password->title }}</h3>
                                    <div class="flex space-x-2">
                                        <button wire:click="copyPassword({{ $password->id }})"
                                                class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                onmouseover="this.style.backgroundColor='#FCA311'; this.style.color='#000000'"
                                                onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                title="Şifreyi kopyala">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="openEditForm({{ $password->id }})"
                                                class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                onmouseover="this.style.backgroundColor='#14213D'; this.style.color='#FFFFFF'"
                                                onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                title="Düzenle">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="deletePassword({{ $password->id }})"
                                                onclick="confirm('Bu şifreyi silmek istediğinizden emin misiniz?') || event.stopImmediatePropagation()"
                                                class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                onmouseover="this.style.backgroundColor='#000000'; this.style.color='#FFFFFF'"
                                                onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                title="Sil">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    @if($password->url)
                                        <div class="flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-2" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            <a href="{{ $password->url }}" target="_blank" class="hover:underline truncate"
                                               style="color: #E5E5E5;"
                                               onmouseover="this.style.color='#FCA311'"
                                               onmouseout="this.style.color='#E5E5E5'">
                                                {{ $password->url }}
                                            </a>
                                        </div>
                                    @endif
                                    <div class="flex items-center text-sm" style="color: #E5E5E5;">
                                        <svg class="w-4 h-4 mr-2" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ $password->username }}
                                    </div>
                                    <div class="flex items-center text-xs" style="color: #E5E5E5;">
                                        <svg class="w-3 h-3 mr-2" style="color: #FCA311;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0V5a2 2 0 00-2 2v6a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2m-6 0v2m0 4v2"></path>
                                        </svg>
                                        {{ $password->created_at->format('d.m.Y') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Modern Table View for Desktop -->
                    <div class="hidden lg:block overflow-hidden rounded-xl border" style="border-color: #E5E5E5;">
                        <table class="min-w-full divide-y" style="border-color: #E5E5E5;">
                            <thead style="background: linear-gradient(135deg, rgba(20, 33, 61, 0.8) 0%, rgba(0, 0, 0, 0.8) 100%); backdrop-filter: blur(10px);">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: #E5E5E5;">Başlık</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: #E5E5E5;">URL</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: #E5E5E5;">Kullanıcı Adı</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: #E5E5E5;">Tarih</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: #E5E5E5;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="background: rgba(229, 229, 229, 0.05); border-color: #E5E5E5;">
                                @foreach($passwords as $password)
                                    <tr class="transition-colors duration-200" style="background: rgba(229, 229, 229, 0.02);"
                                        onmouseover="this.style.background='rgba(252, 163, 17, 0.1)'"
                                        onmouseout="this.style.background='rgba(229, 229, 229, 0.02)'">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold" style="background: linear-gradient(135deg, #FCA311 0%, #14213D 100%);">
                                                        {{ strtoupper(substr($password->title, 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium" style="color: #FFFFFF;">{{ $password->title }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($password->url)
                                                <a href="{{ $password->url }}" target="_blank" class="text-sm hover:underline"
                                                   style="color: #E5E5E5;"
                                                   onmouseover="this.style.color='#FCA311'"
                                                   onmouseout="this.style.color='#E5E5E5'">
                                                    {{ Str::limit($password->url, 30) }}
                                                </a>
                                            @else
                                                <span class="text-sm" style="color: #E5E5E5;">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #E5E5E5;">
                                            {{ $password->username }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #E5E5E5;">
                                            {{ $password->created_at->format('d.m.Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-3">
                                                <button wire:click="copyPassword({{ $password->id }})"
                                                        class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                        style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                        onmouseover="this.style.backgroundColor='#FCA311'; this.style.color='#000000'"
                                                        onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                        title="Şifreyi kopyala">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="openEditForm({{ $password->id }})"
                                                        class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                        style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                        onmouseover="this.style.backgroundColor='#14213D'; this.style.color='#FFFFFF'"
                                                        onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                        title="Düzenle">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="deletePassword({{ $password->id }})"
                                                        onclick="confirm('Bu şifreyi silmek istediğinizden emin misiniz?') || event.stopImmediatePropagation()"
                                                        class="p-3 rounded-lg transition-all duration-200 hover:scale-110"
                                                        style="background-color: rgba(229, 229, 229, 0.2); color: #E5E5E5;"
                                                        onmouseover="this.style.backgroundColor='#000000'; this.style.color='#FFFFFF'"
                                                        onmouseout="this.style.backgroundColor='rgba(229, 229, 229, 0.2)'; this.style.color='#E5E5E5'"
                                                        title="Sil">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $passwords->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 mb-4" style="color: #E5E5E5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium mb-2" style="color: #FFFFFF;">Henüz şifre eklenmemiş</h3>
                        <p class="mb-6" style="color: #E5E5E5;">İlk şifrenizi ekleyerek başlayın.</p>
                        <button wire:click="openCreateForm"
                                class="text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                style="background: linear-gradient(135deg, #FCA311 0%, #14213D 100%);">
                            Şifre Ekle
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Password Form Modal -->
    @if($showForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity"
                     style="background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);"
                     wire:click="closeForm"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                     style="background: rgba(20, 33, 61, 0.95); border: 2px solid #E5E5E5; backdrop-filter: blur(20px);">
                    @livewire('password-form', ['passwordId' => $editingPasswordId])
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('password-saved', () => {
            // Event handled by the component
        });
    });
</script>
