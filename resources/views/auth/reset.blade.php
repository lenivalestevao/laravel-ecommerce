<x-shop title="Choose a new password">
    <h1>Choose a new password</h1>
    <form method="post" action="{{ route('password.update') }}" class="card pad narrow">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="Email" type="email" :value="$email" required />
        <x-field name="password" label="New password" type="password" required />
        <x-field name="password_confirmation" label="Confirm new password" type="password" required />
        <button class="btn" type="submit">Change password</button>
    </form>
</x-shop>
