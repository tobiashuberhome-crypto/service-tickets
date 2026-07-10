@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Kategorien</h1>
            <p class="muted">Kategorien fuer Ersatzteile verwalten.</p>
        </div>
    </div>

    <form class="panel panel-body" method="post" action="{{ route('spare-part-categories.store') }}">
        @csrf
        <div class="form-row">
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" required>
            </div>
            <div>
                <label for="description">Beschreibung</label>
                <input id="description" name="description">
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Kategorie speichern</button>
            </div>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Beschreibung</th>
                    <th>Ersatzteile</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->description ?: '-' }}</td>
                        <td>{{ $category->spare_parts_count }}</td>
                        <td>
                            <form method="post" action="{{ route('spare-part-categories.destroy', $category) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Loeschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Noch keine Kategorien vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
