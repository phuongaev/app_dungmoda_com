<div class="column-labels">
    @foreach($value as $label)
        <div class="badge badge-light-success">
            {{$label['name']}}
        </div>
    @endforeach
</div>
