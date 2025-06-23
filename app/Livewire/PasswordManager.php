<?php

namespace App\Livewire;

use App\Models\Password;
use Livewire\Component;
use Livewire\WithPagination;

class PasswordManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showForm = false;
    public $selectedPassword = null;
    public $editingPasswordId = null;

    protected $queryString = ['search'];
    protected $listeners = ['password-saved' => 'handlePasswordSaved'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateForm()
    {
        $this->selectedPassword = null;
        $this->editingPasswordId = null;
        $this->showForm = true;
    }

    public function openEditForm(Password $password)
    {
        $this->selectedPassword = $password;
        $this->editingPasswordId = $password->id;
        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->selectedPassword = null;
        $this->editingPasswordId = null;
    }

    public function deletePassword(Password $password)
    {
        $password->delete();
        session()->flash('message', 'Şifre başarıyla silindi.');
    }

    public function copyPassword(Password $password)
    {
        // Şifreyi çöz ve clipboard'a kopyalamak için JavaScript'e gönder
        $decryptedPassword = $password->decrypted_password;

        // Debug için log ekle
        \Log::info('Copy password called', [
            'password_id' => $password->id,
            'decrypted_length' => strlen($decryptedPassword ?? ''),
            'decrypted_value' => $decryptedPassword
        ]);

        $this->dispatch('copy-to-clipboard', password: $decryptedPassword);
        session()->flash('message', $password->title . ' şifresi panoya kopyalandı.');
    }

    public function handlePasswordSaved()
    {
        $this->closeForm();
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $passwords = auth()->user()->passwords()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%')
                      ->orWhere('url', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        $recentPasswords = auth()->user()->passwords()
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.password-manager', compact('passwords', 'recentPasswords'));
    }
}
