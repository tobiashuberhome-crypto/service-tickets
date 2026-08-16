@if (session('status'))
    <div class="alert success">{{ session('status') }}</div>
@endif

@if (session('warning'))
    <div class="alert warning">{{ session('warning') }}</div>
@endif

@if ($errors->any())
    <div class="errors">
        <strong>Bitte pruefen:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
