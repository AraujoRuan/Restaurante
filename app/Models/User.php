<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'phone',
        'cpf',
        'birth_date',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'active' => 'boolean',
    ];
    
    /**
     * Aprovar usuário (ativar conta)
     */
    public function approve()
    {
        $this->update(['active' => true]);
        
        // Opcional: enviar email de aprovação
        // $this->sendApprovalEmail();
        
        return $this;
    }
    
    /**
     * Rejeitar usuário (opcional: deletar ou manter inativo)
     */
    public function reject()
    {
        $this->update(['active' => false]);
        
        // Opcional: enviar email de rejeição
        // $this->sendRejectionEmail();
        
        return $this;
    }
    
    /**
     * Enviar email de aprovação (exemplo)
     */
    protected function sendApprovalEmail()
    {
        // Implementar envio de email
        // Mail::to($this->email)->send(new UserApprovedMail($this));
    }
}