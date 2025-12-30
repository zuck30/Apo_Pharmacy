<?php
// app/Http/Middleware/DevelopmentAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevelopmentAuth
{
    public function handle(Request $request, Closure $next)
    {
        // For development only - auto authenticate
        if (!Auth::check()) {
            // Create or find admin user
            $user = \App\Models\User::first();
            
            if (!$user) {
                // Create admin user if doesn't exist
                $user = $this->createAdminUser();
            }
            
            Auth::login($user);
        }
        
        return $next($request);
    }
    
    protected function createAdminUser()
    {
        // First create person
        $person = \App\Models\Person::create([
            'person_type' => 'EMPLOYEE',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@pharmacy.com',
            'mobile' => '255712345678',
            'gender' => 'MALE',
            'status' => 'ACTIVE',
        ]);
        
        // Then create user
        $user = \App\Models\User::create([
            'person_id' => $person->person_id,
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'email' => 'admin@pharmacy.com',
            'role_id' => 1,
            'is_active' => true,
        ]);
        
        return $user;
    }
}