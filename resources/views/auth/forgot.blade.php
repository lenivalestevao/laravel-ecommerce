<x-shop title="Forgot password">
    <h1>Reset your password</h1>
    <form method="post" action="{{ route('password.email') }}" class="card pad narrow">
        @csrf
        <x-field name="email" label="Email" type="email" required />
        <button class="btn" type="submit">Send reset link</button>
    </form>
</x-shop>
