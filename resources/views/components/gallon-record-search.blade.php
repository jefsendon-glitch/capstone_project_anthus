@props(['name', 'page', 'anchor', 'placeholder' => 'Search customer or gallon...'])

<form method="GET" action="{{ route('admin.inventory.gallon-inventory') }}#{{ $anchor }}" class="my-3 flex flex-wrap items-end gap-2">
    @foreach(request()->only(['month', 'loan_search', 'issue_search', 'movement_search', 'loans_page', 'issues_page', 'page']) as $key => $value)
        @if($key !== $name && $key !== $page && is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="min-w-0 flex-1">
        <x-input-label :for="$name" value="Search Records" />
        <x-text-input :id="$name" :name="$name" type="search" :value="request($name)" :placeholder="$placeholder" maxlength="100" class="!py-1.5" />
    </div>
    <x-button type="submit" class="!px-3 !py-1.5 !text-xs">Search</x-button>
    @if(request()->filled($name))
        <x-button as="a" :href="route('admin.inventory.gallon-inventory', request()->except([$name, $page])).'#'.$anchor" variant="secondary" class="!px-3 !py-1.5 !text-xs">Clear</x-button>
    @endif
</form>
