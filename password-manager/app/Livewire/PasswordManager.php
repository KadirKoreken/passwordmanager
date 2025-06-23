<?php

namespace App\Livewire;

use App\Models\Password;
use Livewire\Component;

class PasswordManager extends Component
{
    public function copyPassword(Password $password)
    {
        // Bu işlem frontend tarafında JavaScript ile yapılacak
        $this->dispatch('copy-to-clipboard', password: $password->password);
    }
}
