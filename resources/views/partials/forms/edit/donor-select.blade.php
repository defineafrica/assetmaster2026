<!-- Donor -->
@if (($snipeSettings->full_multiple_donors_support=='1') && (!Auth::user()->isSuperUser()))
    <!-- full donor support is enabled and this user isn't a superadmin -->
    <div class="form-group">
        <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
        <div class="col-md-6">
            <select class="js-data-ajax" disabled="true" data-endpoint="donors" data-placeholder="{{ trans('general.select_donor') }}" name="{{ $fieldname }}" style="width: 100%" aria-label="{{ $fieldname }}"{{ (isset($multiple) && ($multiple=='true')) ? " multiple='multiple'" : '' }}>
                @if ($donor_id = old($fieldname, (isset($item)) ? $item->{$fieldname} : ''))
                    <option value="{{ $donor_id }}" selected="selected" role="option" aria-selected="true"  role="option">
                        {{ (\App\Models\Donor::find($donor_id)) ? \App\Models\Donor::find($donor_id)->name : '' }}
                    </option>
                @else
                    {!! (!isset($multiple) || ($multiple=='false')) ? '<option value="" role="option">'.trans('general.select_donor').'</option>' : ''  !!}
                @endif
            </select>
        </div>
    </div>

@else
    <!-- full donor support is enabled or this user is a superadmin -->
    <div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">
        <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
        <div class="col-md-8">
            <select class="js-data-ajax" data-endpoint="donors" data-placeholder="{{ trans('general.select_donor') }}" name="{{ $fieldname }}" style="width: 100%"{{ (isset($multiple) && ($multiple=='true')) ? " multiple='multiple'" : '' }}>
                @isset ($selected)
                    @foreach ($selected as $donor_id)
                        <option value="{{ $donor_id }}" selected="selected" role="option" aria-selected="true">
                            {{ \App\Models\Donor::find($donor_id)->name }}
                        </option>
                    @endforeach
                @endisset
                @if ($donor_id = old($fieldname, (isset($item)) ? $item->{$fieldname} : ''))
                    <option value="{{ $donor_id }}" selected="selected">
                        {{ (\App\Models\Donor::find($donor_id)) ? \App\Models\Donor::find($donor_id)->name : '' }}
                    </option>
                @else
                    {!! (!isset($multiple) || ($multiple=='false')) ? '<option value="" role="option">'.trans('general.select_donor').'</option>' : ''  !!}
                @endif
            </select>
        </div>
        {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}

    </div>

@endif
