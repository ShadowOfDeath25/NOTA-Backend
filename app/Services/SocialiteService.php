<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class SocialiteService
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function redirectToProvider(string $provider): RedirectResponse|Provider
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider): ?User
    {
        $socialiteUser = Socialite::driver($provider)->stateless()->user();

        return $this->findOrCreateUser($provider, $socialiteUser);
    }

    public function findOrCreateUser(string $provider, SocialiteUser $socialiteUser): User
    {
        $providerField = "{$provider}_id";

        $user = User::where($providerField, $socialiteUser->getId())
            ->orWhere('email', $socialiteUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->{$providerField}) {
                $user->update([
                    $providerField => $socialiteUser->getId(),
                ]);
            }

            return $user;
        }

        return User::create([
            'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'User',
            'email' => $socialiteUser->getEmail(),
            'password' => bcrypt(Str::random(32)),
            $providerField => $socialiteUser->getId(),
        ]);
    }

    public function loginUser(User $user): User
    {
        Auth::login($user);

        return $user;
    }
}
