<x-shop title="Log in">
    <h1>Log in</h1>
    <form method="post" action="{{ route('login') }}" class="card pad narrow">
        @csrf
        <x-field name="email" label="Email" type="email" required autofocus />
        <x-field name="password" label="Password" type="password" required />
        <label class="radio"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="btn" type="submit">Log in</button>
        <p class="muted"><a href="{{ route('password.request') }}">Forgot your password?</a> &middot; <a href="{{ route('register') }}">Create an account</a></p>
    </form>
</x-shop>
