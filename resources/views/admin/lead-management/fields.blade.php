@php
    $editing = $editing ?? false;
    $prefix = $editing ? 'edit_' : 'add_';
    $fields = \App\Support\LeadFormFields::FIELDS;
    $lockedFields = ['name', 'phone', 'email', 'message', 'source_page_name', 'project_id', 'source_website'];
@endphp
<div class="row g-3">
    @if($editing)
        <div class="col-12"><p class="text-muted mb-0">Contact details, message, page, source, and project are read-only.</p></div>
    @endif
    @foreach($fields as $field => [$label, $kind])
        @php
            $suffix = match($field) {
                'source_website' => $editing ? 'source_website' : 'source',
                'assigned_to' => 'assigned',
                'follow_up_date' => $editing ? 'follow_up_date' : 'followup',
                default => $field,
            };
            $id = $prefix . $suffix;
            $locked = $editing && in_array($field, $lockedFields, true);
        @endphp
        <div @if($field === 'deactivate_reason') id="{{ $prefix }}deactivate_reason_group" hidden @endif class="{{ $kind === 'textarea' ? 'col-12' : 'col-md-6' }}">
            <label class="form-label" for="{{ $id }}">{{ $label }}</label>
            @if($kind === 'select')
                <select name="{{ $field }}" id="{{ $id }}" class="form-select" @disabled($locked) @required(!$editing && $field === 'source_website')>
                    @if($field === 'source_website')
                        <option value="">Select a source</option>
                        @foreach($manualLeadSources as $sourceLabel => $value)
                            <option value="{{ $value }}">{{ $sourceLabel }}</option>
                        @endforeach
                    @elseif($field === 'project_id')
                        <option value="">No project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    @elseif($field !== 'assigned_to')
                        @if(in_array($field, ['lead_source', 'inventory', 'deactivate_reason']))
                            <option value="">None</option>
                        @endif
                        @foreach($leadOptions[$field] as $value)
                            <option value="{{ $value }}">{{ ucwords(str_replace(['_', '-'], ' ', $value)) }}</option>
                        @endforeach
                    @endif
                </select>
            @elseif($kind === 'textarea')
                <textarea name="{{ $field }}" id="{{ $id }}" class="form-control" rows="3" @readonly($locked)></textarea>
            @else
                <input type="{{ $kind }}" name="{{ $field }}" id="{{ $id }}" class="form-control"
                    @readonly($locked) @required(!$editing && in_array($field, ['name', 'phone']))
                    @if($kind === 'number') min="0" step="0.01" @endif>
            @endif
        </div>
    @endforeach
</div>
