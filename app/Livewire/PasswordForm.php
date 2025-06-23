<?php

namespace App\Livewire;

use App\Models\Password;
use Livewire\Component;

class PasswordForm extends Component
{
    public $passwordId;
    public $title = '';
    public $url = '';
    public $username = '';
    public $password = '';
    public $showPassword = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'url' => 'nullable|string|max:255',
        'username' => 'required|string|max:255',
        'password' => 'required|string|min:1',
    ];

    protected $messages = [
        'title.required' => 'Başlık alanı zorunludur.',
        'username.required' => 'Kullanıcı adı/E-posta alanı zorunludur.',
        'password.required' => 'Şifre alanı zorunludur.',
    ];

    public function mount($password = null)
    {
        if ($password) {
            $this->passwordId = $password->id;
            $this->title = $password->title;
            $this->url = $password->url;
            $this->username = $password->username;
            $this->password = $password->password;
        }
    }

    public function generatePassword()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $this->password = '';
        for ($i = 0; $i < 12; $i++) {
            $this->password .= $characters[rand(0, strlen($characters) - 1)];
        }
    }

    public function togglePasswordVisibility()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function save()
    {
        $this->validate();

        if ($this->passwordId) {
            // Güncelleme
            $password = Password::findOrFail($this->passwordId);
            $password->update([
                'title' => $this->title,
                'url' => $this->url,
                'username' => $this->username,
                'password' => $this->password,
            ]);
            session()->flash('message', 'Şifre başarıyla güncellendi.');
        } else {
            // Yeni kayıt
            Password::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'url' => $this->url,
                'username' => $this->username,
                'password' => $this->password,
            ]);
            session()->flash('message', 'Şifre başarıyla kaydedildi.');
        }

        $this->dispatch('password-saved');
        $this->reset(['title', 'url', 'username', 'password', 'passwordId', 'showPassword']);
    }

    public function render()
    {
        return view('livewire.password-form');
    }
}
