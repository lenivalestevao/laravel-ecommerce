<x-shop title="Create account">
    <h1>Create an account</h1>
    <form method="post" action="{{ route('register') }}" class="card pad narrow">
        @csrf
        <x-field name="name" label="Full name" required />
        <x-field name="email" label="Email" type="email" required />
        <x-field name="phone" label="Phone" />
        <x-field name="password" label="Password" type="password" hint="At least 8 characters" required />
        <x-field name="password_confirmation" label="Confirm password" type="password" required />
        <button class="btn" type="submit">Register</button>
    </form>
</x-shop>
