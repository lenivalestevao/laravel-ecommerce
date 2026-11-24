@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->has('cart'))
    <div class="alert alert-error" role="alert">{{ $errors->first('cart') }}</div>
@endif
