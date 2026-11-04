<fieldset @disabled(!$editable)>
@for($i=0;$i<5;$i++)
    @php($q=$set->questions->get($i))
    <details class="question-editor" @if($i===0 || !$q || $errors->any()) open @endif>
        <summary><strong>Câu {{ $i+1 }}. {{ old('questions.'.$i.'.question_text',$q?->question_text) ?: 'Nhập câu hỏi mới' }}</strong><span>{{ $editable?'Sửa':'Xem' }} <span aria-hidden="true">⌄</span></span></summary>
        <div class="question-editor-content">
            <div class="field-group"><label for="question_{{ $i }}">Nội dung câu hỏi</label><textarea class="form-control" id="question_{{ $i }}" name="questions[{{ $i }}][question_text]" required maxlength="2000">{{ old('questions.'.$i.'.question_text',$q?->question_text) }}</textarea></div>
            <div><label>Các đáp án <span class="option-hint">Chọn một đáp án đúng bằng nút tròn.</span></label>
                @for($j=0;$j<4;$j++)<div class="option-edit"><input id="correct_{{ $i }}_{{ $j }}" type="radio" name="questions[{{ $i }}][correct_option_index]" value="{{ $j }}" required aria-label="Chọn {{ chr(65+$j) }} làm đáp án đúng câu {{ $i+1 }}" @checked((string)old('questions.'.$i.'.correct_option_index',$q?->correct_option_index)===(string)$j)><label for="option_{{ $i }}_{{ $j }}">{{ chr(65+$j) }}.</label><input class="form-control" type="text" id="option_{{ $i }}_{{ $j }}" name="questions[{{ $i }}][options][]" value="{{ old('questions.'.$i.'.options.'.$j,$q?->options[$j]??'') }}" required maxlength="1000"></div>@endfor
            </div>
            <div class="field-group"><label for="explanation_{{ $i }}">Giải thích</label><textarea class="form-control" id="explanation_{{ $i }}" name="questions[{{ $i }}][explanation]" required maxlength="5000">{{ old('questions.'.$i.'.explanation',$q?->explanation) }}</textarea></div>
        </div>
    </details>
@endfor
</fieldset>
