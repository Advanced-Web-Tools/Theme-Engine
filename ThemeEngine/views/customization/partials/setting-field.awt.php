<div class="form-field">
    <label class="form-label" for="setting-{{ $key }}">
        {{ $field['label'] }}
    </label>

    @if($field['type'] === 'boolean')
        <label class="form-switch">
            <input type="hidden" name="settings[{{ $key }}]" value="0">
            <input id="setting-{{ $key }}"
                   aria-label="{{ $field['label'] }}"
                   type="checkbox"
                   name="settings[{{ $key }}]"
                   value="1"
                   @if(isset($field['description'])) aria-describedby="setting-help-{{ $key }}" @endif
                   @if($values[$key]) checked @endif>
            <span class="switch-track" aria-hidden="true"></span>
        </label>
    @elseif($field['type'] === 'textarea')
        <textarea class="form-control"
                  id="setting-{{ $key }}"
                  name="settings[{{ $key }}]"
                  maxlength="{{ $field['max_length'] ?? 4096 }}"
                  @if(isset($field['description'])) aria-describedby="setting-help-{{ $key }}" @endif
                  @if($field['required'] ?? false) required @endif>{{ $values[$key] }}</textarea>
    @elseif($field['type'] === 'select')
        <select class="form-select"
                id="setting-{{ $key }}"
                name="settings[{{ $key }}]"
                @if(isset($field['description'])) aria-describedby="setting-help-{{ $key }}" @endif>
            @foreach($field['options'] as $value=>$label)
                <option value="{{ $value }}"
                        @if((string)$values[$key] === (string)$value) selected @endif>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    @else
        <input class="form-control"
               id="setting-{{ $key }}"
               type="{{ in_array($field['type'],['color','url','number'],true) ? $field['type'] : 'text' }}"
               name="settings[{{ $key }}]"
               value="{{ $values[$key] }}"
               @if(isset($field['description'])) aria-describedby="setting-help-{{ $key }}" @endif
               @if($field['type'] === 'number')
                   step="any"
               @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
               @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
               @else
                   maxlength="{{ $field['max_length'] ?? 4096 }}"
               @endif
               @if($field['required'] ?? false) required @endif>
    @endif

    @if(isset($field['description']))
        <small id="setting-help-{{ $key }}">
            {{ $field['description'] }}
        </small>
    @endif
</div>
