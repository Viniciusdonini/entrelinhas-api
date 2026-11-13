<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            return $this->issueSession($user);
        });
    }

    public function login(array $data): array
    {
        $user = $this->users->findByEmail($data['email']);
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        return $this->issueSession($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    private function issueSession(User $user): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken('entrelinhas-mobile', ['*'], now()->addHours(8))->plainTextToken,
        ];
    }
}
